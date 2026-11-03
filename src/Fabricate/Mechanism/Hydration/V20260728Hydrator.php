<?php

namespace Fabricate\Mechanism\Hydration;

use Fabricate\Client\ClientCapabilitiesFactory;
use Fabricate\Contracts\Mechanism\VersionHydrator;
use Fabricate\Contracts\Schema\Concerns\Role;
use Fabricate\Contracts\Schema\ElicitAction;
use Fabricate\Contracts\Schema\IconTheme;
use Fabricate\Contracts\Schema\IncludeContext;
use Fabricate\Contracts\Schema\LoggingLevel;
use Fabricate\Contracts\Schema\Messaging\MCPError;
use Fabricate\Contracts\Schema\Messaging\Notification;
use Fabricate\Contracts\Schema\Messaging\Request;
use Fabricate\Contracts\Schema\Messaging\Result;
use Fabricate\Contracts\Schema\ProgressToken;
use Fabricate\Contracts\Schema\RPCErrorCode;
use Fabricate\Contracts\Schema\ToolChoiceMode;
use Fabricate\Mechanism\Decoding\DecodedError;
use Fabricate\Mechanism\Decoding\DecodedNotification;
use Fabricate\Mechanism\Decoding\DecodedRequest;
use Fabricate\Mechanism\Decoding\DecodedResponse;
use Fabricate\Schema\Common\Concerns\Icon;
use Fabricate\Schema\V20260728\Capabilities\Elicitation\BooleanSchema;
use Fabricate\Schema\V20260728\Capabilities\Elicitation\LegacyTitledEnumSchema;
use Fabricate\Schema\V20260728\Capabilities\Elicitation\NumberSchema;
use Fabricate\Schema\V20260728\Capabilities\Elicitation\StringSchema;
use Fabricate\Schema\V20260728\Capabilities\Elicitation\TitledMultiSelectEnumSchema;
use Fabricate\Schema\V20260728\Capabilities\Elicitation\TitledSingleSelectEnumSchema;
use Fabricate\Schema\V20260728\Capabilities\Elicitation\UntitledMultiSelectEnumSchema;
use Fabricate\Schema\V20260728\Capabilities\Elicitation\UntitledSingleSelectEnumSchema;
use Fabricate\Schema\V20260728\Capabilities\ClientCapabilities;
use Fabricate\Schema\V20260728\Capabilities\Prompts\PromptDefinition;
use Fabricate\Schema\V20260728\Capabilities\ServerCapabilities;
use Fabricate\Schema\V20260728\Capabilities\Prompts\PromptArgument;
use Fabricate\Schema\V20260728\Capabilities\Prompts\PromptMessage;
use Fabricate\Schema\V20260728\Capabilities\Resources\BlobResourceContents;
use Fabricate\Schema\V20260728\Capabilities\Resources\ResourceDefinition;
use Fabricate\Schema\V20260728\Capabilities\Resources\ResourceTemplateDefinition;
use Fabricate\Schema\V20260728\Capabilities\Resources\SubscriptionFilter;
use Fabricate\Schema\V20260728\Capabilities\Resources\TextResourceContents;
use Fabricate\Schema\V20260728\Capabilities\Roots\Root;
use Fabricate\Schema\V20260728\Capabilities\Sampling\ModelHint;
use Fabricate\Schema\V20260728\Capabilities\Sampling\ModelPreferences;
use Fabricate\Schema\V20260728\Capabilities\Sampling\SamplingMessage;
use Fabricate\Schema\V20260728\Capabilities\Sampling\ToolChoice;
use Fabricate\Schema\V20260728\Capabilities\Tools\ToolDefinition;
use Fabricate\Schema\V20260728\Capabilities\Tools\ToolAnnotations;
use Fabricate\Schema\V20260728\Concerns\Annotations;
use Fabricate\Schema\V20260728\Concerns\AudioContent;
use Fabricate\Schema\V20260728\Concerns\CacheScope;
use Fabricate\Schema\V20260728\Concerns\ContentBlock;
use Fabricate\Schema\V20260728\Concerns\EmbeddedResource;
use Fabricate\Schema\V20260728\Concerns\ImageContent;
use Fabricate\Schema\V20260728\Concerns\InputRequest;
use Fabricate\Schema\V20260728\Concerns\InputResponse;
use Fabricate\Schema\V20260728\Concerns\MCPErrorCode;
use Fabricate\Schema\V20260728\Concerns\PrimitiveSchemaDefinition;
use Fabricate\Schema\V20260728\Concerns\PromptReference;
use Fabricate\Schema\V20260728\Concerns\ResourceLink;
use Fabricate\Schema\V20260728\Concerns\ResourceTemplateReference;
use Fabricate\Schema\V20260728\Concerns\ResultTypeEnum;
use Fabricate\Schema\V20260728\Concerns\SamplingMessageContentBlock;
use Fabricate\Schema\V20260728\Concerns\TextContent;
use Fabricate\Schema\V20260728\Concerns\ToolResultContent;
use Fabricate\Schema\V20260728\Concerns\ToolUseContent;
use Fabricate\Schema\V20260728\Implementation;
use Fabricate\Schema\V20260728\JSONArray;
use Fabricate\Schema\V20260728\JSONObject;
use Fabricate\Schema\V20260728\JSONValue;
use Fabricate\Schema\V20260728\Messaging;
use Fabricate\Schema\V20260728\Messaging\Inputs\InputRequests;
use Fabricate\Schema\V20260728\Messaging\Inputs\InputRequiredResult;
use Fabricate\Schema\V20260728\Messaging\Inputs\InputResponses;
use Fabricate\Schema\V20260728\Messaging\MCPError as VersionError;
use Fabricate\Schema\V20260728\Messaging\MetaObjects\NotificationMetaObject;
use Fabricate\Schema\V20260728\Messaging\MetaObjects\RequestMetaObject;
use Fabricate\Schema\V20260728\Messaging\MetaObjects\ResultMetaObject;
use Fabricate\Schema\V20260728\Messaging\MetaObjects\SubscriptionsListenResultMetaObject;
use Fabricate\Schema\V20260728\Messaging\Params;
use Fabricate\Schema\V20260728\MetaObject;
use Fabricate\Server\ServerCapabilitiesFactory;

/**
 * Builds the 2026-07-28 schema classes. Every request carries its protocol version and client capabilities
 * in _meta, so a message without them does not fit this version. Roots, sampling and elicitation travel as
 * input requests inside results and input responses inside params, never as JSON-RPC messages of their own.
 */
final class V20260728Hydrator implements VersionHydrator
{
    private const string VERSION = '2026-07-28';

    private const string META_PROTOCOL_VERSION = 'io.modelcontextprotocol/protocolVersion';
    private const string META_CLIENT_CAPABILITIES = 'io.modelcontextprotocol/clientCapabilities';
    private const string META_CLIENT_INFO = 'io.modelcontextprotocol/clientInfo';
    private const string META_LOG_LEVEL = 'io.modelcontextprotocol/logLevel';
    private const string META_SERVER_INFO = 'io.modelcontextprotocol/serverInfo';
    private const string META_SUBSCRIPTION_ID = 'io.modelcontextprotocol/subscriptionId';

    public function request(DecodedRequest $message): Request
    {
        $params = new Wire($message->params ?? []);
        $meta = $this->requestMeta($params);
        $id = $message->id;

        return match($message->method) {
            'server/discover' => new Messaging\Discover\DiscoverRequest($id, new Params\RequestParams($meta)),
            'tools/list' => new Messaging\Tools\ListToolsRequest($id, new Params\PaginatedRequestParams($meta, $params->optionalCursor('cursor'))),
            'tools/call' => new Messaging\Tools\CallToolRequest($id, new Params\CallToolRequestParams(
                $params->string('name'),
                $meta,
                $params->optionalObject('arguments'),
                $this->inputResponses($params),
                $params->optionalString('requestState'),
            )),
            'prompts/list' => new Messaging\Prompts\ListPromptsRequest($id, new Params\PaginatedRequestParams($meta, $params->optionalCursor('cursor'))),
            'prompts/get' => new Messaging\Prompts\GetPromptRequest($id, new Params\GetPromptRequestParams(
                $params->string('name'),
                $meta,
                $params->optionalObject('arguments'),
                $this->inputResponses($params),
                $params->optionalString('requestState'),
            )),
            'resources/list' => new Messaging\Resources\ListResourcesRequest($id, new Params\PaginatedRequestParams($meta, $params->optionalCursor('cursor'))),
            'resources/templates/list' => new Messaging\Resources\ListResourceTemplatesRequest($id, new Params\PaginatedRequestParams($meta, $params->optionalCursor('cursor'))),
            'resources/read' => new Messaging\Resources\ReadResourceRequest($id, new Params\ReadResourceRequestParams(
                $params->string('uri'),
                $meta,
                $this->inputResponses($params),
                $params->optionalString('requestState'),
            )),
            'subscriptions/listen' => new Messaging\Resources\SubscriptionsListenRequest($id, new Params\SubscriptionsListenRequestParams($this->subscriptionFilter($params->child('notifications')), $meta)),
            // The io.modelcontextprotocol/tasks extension.
            'tasks/get' => new Messaging\Tasks\GetTaskRequest($id, new Params\TaskRequestParams($params->string('taskId'), $meta)),
            'tasks/cancel' => new Messaging\Tasks\CancelTaskRequest($id, new Params\TaskRequestParams($params->string('taskId'), $meta)),
            'tasks/update' => new Messaging\Tasks\UpdateTaskRequest($id, new Params\UpdateTaskRequestParams($params->string('taskId'), $this->inputResponses($params) ?? throw new HydrationException('params.inputResponses is required.'), $meta)),
            'completion/complete' => new Messaging\Autocomplete\CompleteRequest($id, new Params\CompleteRequestParams(
                $this->reference($params->child('ref')),
                $params->child('argument')->string('name'),
                $params->child('argument')->string('value'),
                $meta,
                $params->optionalChild('context')?->optionalObject('arguments'),
            )),
            default => throw $this->unknownMethod($message->method),
        };
    }

    public function notification(DecodedNotification $message): Notification
    {
        $params = new Wire($message->params ?? []);
        $meta = $this->notificationMeta($params);
        $plain = is_null($message->params) ? null : new Params\NotificationParams($meta);

        return match($message->method) {
            'notifications/cancelled' => new Messaging\Cancellation\CancelledNotification(new Params\CancelledNotificationParams($params->requestId('requestId'), $params->optionalString('reason'), $meta)),
            'notifications/progress' => new Messaging\Progress\ProgressNotification(new Params\ProgressNotificationParams(
                new ProgressToken($params->requestId('progressToken')->id),
                $params->number('progress'),
                $params->optionalNumber('total'),
                $params->optionalString('message'),
                $meta,
            )),
            'notifications/tools/list_changed' => new Messaging\Tools\ToolListChangedNotification($plain),
            'notifications/prompts/list_changed' => new Messaging\Prompts\PromptListChangedNotification($plain),
            'notifications/resources/list_changed' => new Messaging\Resources\ResourceListChangedNotification($plain),
            'notifications/resources/updated' => new Messaging\Resources\ResourceUpdatedNotification(new Params\ResourceUpdatedNotificationParams($params->string('uri'), $meta)),
            'notifications/subscriptions/acknowledged' => new Messaging\Resources\SubscriptionsAcknowledgedNotification(new Params\SubscriptionsAcknowledgedNotificationParams($this->subscriptionFilter($params->child('notifications')), $meta)),
            'notifications/message' => new Messaging\Logging\LoggingMessageNotification(new Params\LoggingMessageNotificationParams(
                $params->enum('level', LoggingLevel::class),
                $params->has('data') ? $params->data['data'] : throw $params->invalid('data', 'present'),
                $params->optionalString('logger'),
                $meta,
            )),
            default => throw $this->unknownMethod($message->method),
        };
    }

    public function result(DecodedResponse $message, string $method): Result
    {
        if(!is_array($message->result) || array_is_list($message->result) && $message->result !== [])
        {
            throw new HydrationException('result must be an object.');
        }

        $result = new Wire($message->result, 'result');
        if(!$result->has('resultType')) throw new NotThisVersion('result.resultType is required and must be "complete" or "input_required".');

        $type = $result->enum('resultType', ResultTypeEnum::class);
        $meta = $this->resultMeta($result);

        if($type === ResultTypeEnum::INPUT_REQUIRED)
        {
            if(!in_array($method, ['tools/call', 'prompts/get', 'resources/read'])) throw $result->invalid('resultType', '"complete" for '.$method);

            return new InputRequiredResult($type, $this->inputRequests($result), $result->optionalString('requestState'), $meta);
        }

        return match($method) {
            'server/discover' => new Messaging\Discover\DiscoverResult(
                $result->list('supportedVersions'),
                $this->serverCapabilities($result->object('capabilities')),
                $result->int('ttlMs'),
                $result->enum('cacheScope', CacheScope::class),
                $type,
                $result->optionalString('instructions'),
                $meta,
            ),
            'tools/list' => new Messaging\Tools\ListToolsResult(array_map($this->tool(...), $result->children('tools')), $result->int('ttlMs'), $result->enum('cacheScope', CacheScope::class), $result->optionalCursor('nextCursor'), $type, $meta),
            'tools/call' => new Messaging\Tools\CallToolResult(
                array_map($this->content(...), $result->children('content')),
                $result->has('structuredContent') ? $result->data['structuredContent'] : null,
                $result->optionalBool('isError'),
                $type,
                $meta,
            ),
            'prompts/list' => new Messaging\Prompts\ListPromptsResult(array_map($this->prompt(...), $result->children('prompts')), $result->int('ttlMs'), $result->enum('cacheScope', CacheScope::class), $result->optionalCursor('nextCursor'), $type, $meta),
            'prompts/get' => new Messaging\Prompts\GetPromptResult(array_map($this->promptMessage(...), $result->children('messages')), $result->optionalString('description'), $type, $meta),
            'resources/list' => new Messaging\Resources\ListResourcesResult(array_map($this->resource(...), $result->children('resources')), $result->int('ttlMs'), $result->enum('cacheScope', CacheScope::class), $result->optionalCursor('nextCursor'), $type, $meta),
            'resources/templates/list' => new Messaging\Resources\ListResourceTemplatesResult(array_map($this->resourceTemplate(...), $result->children('resourceTemplates')), $result->int('ttlMs'), $result->enum('cacheScope', CacheScope::class), $result->optionalCursor('nextCursor'), $type, $meta),
            'resources/read' => new Messaging\Resources\ReadResourceResult(array_map($this->resourceContents(...), $result->children('contents')), $result->int('ttlMs'), $result->enum('cacheScope', CacheScope::class), $type, $meta),
            'subscriptions/listen' => new Messaging\Resources\SubscriptionsListenResult($this->subscriptionsListenMeta($result), $type),
            'completion/complete' => new Messaging\Autocomplete\CompleteResult(
                $result->child('completion')->list('values'),
                $result->child('completion')->optionalInt('total'),
                $result->child('completion')->optionalBool('hasMore'),
                $type,
                $meta,
            ),
            default => throw $this->unknownMethod($method),
        };
    }

    public function error(DecodedError $message): MCPError
    {
        if(is_null(RPCErrorCode::tryFrom($message->code)) && is_null(MCPErrorCode::tryFrom($message->code)))
        {
            throw new HydrationException("error.code [{$message->code}] is not a code this version knows.", RPCErrorCode::INVALID_REQUEST);
        }

        return new VersionError($message->code, $message->message, $message->data);
    }

    private function unknownMethod(string $method): HydrationException
    {
        return new HydrationException("Method not found: [{$method}].", RPCErrorCode::METHOD_NOT_FOUND);
    }

    /** @param array<string, mixed> $capabilities */
    private function clientCapabilities(array $capabilities): ClientCapabilities
    {
        $built = new ClientCapabilitiesFactory()->make(self::VERSION, CapabilityArguments::client($capabilities));
        if(!$built instanceof ClientCapabilities) throw new HydrationException('No client capabilities class for '.self::VERSION.'.', RPCErrorCode::INTERNAL_ERROR);

        return $built;
    }

    /** @param array<string, mixed> $capabilities */
    private function serverCapabilities(array $capabilities): ServerCapabilities
    {
        $built = new ServerCapabilitiesFactory()->make(self::VERSION, CapabilityArguments::server($capabilities));
        if(!$built instanceof ServerCapabilities) throw new HydrationException('No server capabilities class for '.self::VERSION.'.', RPCErrorCode::INTERNAL_ERROR);

        return $built;
    }

    private function requestMeta(Wire $params): RequestMetaObject
    {
        $meta = $params->optionalChild('_meta');
        if(is_null($meta) || !$meta->has(self::META_PROTOCOL_VERSION)) throw new NotThisVersion('params._meta.'.self::META_PROTOCOL_VERSION.' is required and must be a string.');

        $record = $meta->data;
        $version = $meta->string(self::META_PROTOCOL_VERSION);
        if($version !== self::VERSION) throw new NotThisVersion("params._meta.".self::META_PROTOCOL_VERSION." [{$version}] is not a supported protocol version.");

        $capabilities = $meta->object(self::META_CLIENT_CAPABILITIES);
        $info = $meta->optionalChild(self::META_CLIENT_INFO);
        $level = $meta->optionalEnum(self::META_LOG_LEVEL, LoggingLevel::class);
        $token = $meta->has('progressToken') ? new ProgressToken($meta->requestId('progressToken')->id) : null;
        unset($record[self::META_PROTOCOL_VERSION], $record[self::META_CLIENT_CAPABILITIES], $record[self::META_CLIENT_INFO], $record[self::META_LOG_LEVEL], $record['progressToken']);

        return new RequestMetaObject(
            $version,
            $this->clientCapabilities($capabilities),
            is_null($info) ? null : $this->implementation($info),
            $token,
            $level,
            $record,
        );
    }

    private function notificationMeta(Wire $params): ?NotificationMetaObject
    {
        $meta = $params->optionalChild('_meta');
        if(is_null($meta)) return null;

        $record = $meta->data;
        unset($record[self::META_SUBSCRIPTION_ID]);

        return new NotificationMetaObject($meta->optionalRequestId(self::META_SUBSCRIPTION_ID), $record);
    }

    private function resultMeta(Wire $result): ?ResultMetaObject
    {
        $meta = $result->optionalChild('_meta');
        if(is_null($meta)) return null;

        $record = $meta->data;
        unset($record[self::META_SERVER_INFO]);
        $info = $meta->optionalChild(self::META_SERVER_INFO);

        return new ResultMetaObject(is_null($info) ? null : $this->implementation($info), $record);
    }

    private function subscriptionsListenMeta(Wire $result): SubscriptionsListenResultMetaObject
    {
        $meta = $result->child('_meta');
        $record = $meta->data;
        unset($record[self::META_SERVER_INFO], $record[self::META_SUBSCRIPTION_ID]);
        $info = $meta->optionalChild(self::META_SERVER_INFO);

        return new SubscriptionsListenResultMetaObject($meta->requestId(self::META_SUBSCRIPTION_ID), is_null($info) ? null : $this->implementation($info), $record);
    }

    private function metaObject(Wire $owner): ?MetaObject
    {
        $meta = $owner->optionalObject('_meta');

        return is_null($meta) ? null : new MetaObject($meta);
    }

    private function subscriptionFilter(Wire $filter): SubscriptionFilter
    {
        return new SubscriptionFilter(
            $filter->optionalBool('toolsListChanged'),
            $filter->optionalBool('promptsListChanged'),
            $filter->optionalBool('resourcesListChanged'),
            $filter->optionalList('resourceSubscriptions'),
            $filter->optionalList('taskIds'),
        );
    }

    /** The server's input requests inside an input-required result, keyed by request id: roots, sampling or elicitation, each tagged by method. */
    private function inputRequests(Wire $result): ?InputRequests
    {
        $requests = $result->optionalKeyedChildren('inputRequests');
        if(is_null($requests)) return null;

        return new InputRequests(array_map($this->inputRequest(...), $requests));
    }

    private function inputRequest(Wire $request): InputRequest
    {
        $params = new Wire($request->optionalObject('params') ?? [], "{$request->path}.params");

        return match($request->string('method')) {
            'roots/list' => new Messaging\Roots\ListRootsRequest($this->metaObject($params)),
            'sampling/createMessage' => new Messaging\Sampling\CreateMessageRequest(new Params\CreateMessageRequestParams(
                array_map($this->samplingMessage(...), $params->children('messages')),
                $params->int('maxTokens'),
                $this->modelPreferences($params->optionalChild('modelPreferences')),
                $params->optionalString('systemPrompt'),
                $params->optionalEnum('includeContext', IncludeContext::class),
                $params->optionalNumber('temperature'),
                $params->optionalList('stopSequences'),
                $params->has('metadata') ? $this->jsonObject($params->object('metadata')) : null,
                $params->has('tools') ? array_map($this->tool(...), $params->children('tools')) : null,
                $params->has('toolChoice') ? new ToolChoice($params->child('toolChoice')->optionalEnum('mode', ToolChoiceMode::class)) : null,
            )),
            'elicitation/create' => new Messaging\Elicitation\ElicitRequest(match($params->optionalString('mode') ?? 'form') {
                'form' => new Params\ElicitRequestFormParams(
                    $params->string('message'),
                    $this->elicitationProperties($params->child('requestedSchema')),
                    $params->child('requestedSchema')->optionalList('required'),
                    $params->child('requestedSchema')->optionalString('$schema'),
                ),
                'url' => new Params\ElicitRequestURLParams($params->string('message'), $params->string('url')),
                default => throw $params->invalid('mode', '"form" or "url"'),
            }),
            default => throw $request->invalid('method', '"roots/list", "sampling/createMessage" or "elicitation/create"'),
        };
    }

    /** The client's answers to input requests, keyed by the id each answers and carried on its retried request; each is recognised by its members. */
    private function inputResponses(Wire $params): ?InputResponses
    {
        $responses = $params->optionalKeyedChildren('inputResponses');
        if(is_null($responses)) return null;

        return new InputResponses(array_map($this->inputResponse(...), $responses));
    }

    private function inputResponse(Wire $response): InputResponse
    {
        return match(true) {
            $response->has('roots') => new Messaging\Roots\ListRootsResult(
                array_map(fn(Wire $root) => new Root($root->string('uri'), $root->optionalString('name'), $this->metaObject($root)), $response->children('roots')),
            ),
            $response->has('model') => new Messaging\Sampling\CreateMessageResult(
                $response->string('model'),
                $response->enum('role', Role::class),
                $this->samplingContents($response, 'content'),
                $response->optionalString('stopReason'),
                $this->metaObject($response),
            ),
            $response->has('action') => new Messaging\Elicitation\ElicitResult($response->enum('action', ElicitAction::class), $response->optionalObject('content')),
            default => throw new HydrationException("{$response->path} must be a roots, sampling or elicitation response."),
        };
    }

    private function jsonValue(mixed $value): JSONValue
    {
        return new JSONValue(match(true) {
            is_array($value) && array_is_list($value) && $value !== [] => new JSONArray(array_map($this->jsonValue(...), $value)),
            is_array($value) => $this->jsonObject($value),
            default => $value,
        });
    }

    /** @param array<string, mixed> $object */
    private function jsonObject(array $object): JSONObject
    {
        return new JSONObject(array_map($this->jsonValue(...), $object));
    }

    /** @return array<string, PrimitiveSchemaDefinition> keyed by property name, as requestedSchema.properties is. */
    private function elicitationProperties(Wire $schema): array
    {
        $properties = [];
        foreach($schema->object('properties') as $name => $definition)
        {
            if(!is_array($definition)) throw new HydrationException("{$schema->path}.properties.{$name} must be an object.");

            $p = new Wire($definition, "{$schema->path}.properties.{$name}");
            $properties[$name] = match($p->string('type')) {
                'boolean' => new BooleanSchema($p->optionalString('title'), $p->optionalString('description'), $p->optionalBool('default')),
                'number', 'integer' => new NumberSchema($p->string('type') === 'integer', $p->optionalString('title'), $p->optionalString('description'), $p->optionalNumber('minimum'), $p->optionalNumber('maximum'), $p->optionalNumber('default')),
                'string' => match(true) {
                    $p->has('oneOf') => new TitledSingleSelectEnumSchema($p->list('oneOf'), $p->optionalString('title'), $p->optionalString('description'), $p->optionalString('default')),
                    $p->has('enumNames') => new LegacyTitledEnumSchema($p->list('enum'), $p->list('enumNames'), $p->optionalString('title'), $p->optionalString('description'), $p->optionalString('default')),
                    $p->has('enum') => new UntitledSingleSelectEnumSchema($p->list('enum'), $p->optionalString('title'), $p->optionalString('description'), $p->optionalString('default')),
                    default => new StringSchema($p->optionalString('title'), $p->optionalString('description'), $p->optionalInt('minLength'), $p->optionalInt('maxLength'), $p->optionalString('format'), $p->optionalString('default')),
                },
                'array' => $p->child('items')->has('anyOf')
                    ? new TitledMultiSelectEnumSchema($p->child('items')->list('anyOf'), $p->optionalString('title'), $p->optionalString('description'), $p->optionalInt('minItems'), $p->optionalInt('maxItems'), $p->optionalList('default'))
                    : new UntitledMultiSelectEnumSchema($p->child('items')->list('enum'), $p->optionalString('title'), $p->optionalString('description'), $p->optionalInt('minItems'), $p->optionalInt('maxItems'), $p->optionalList('default')),
                default => throw $p->invalid('type', '"boolean", "string", "number", "integer" or "array"'),
            };
        }

        return $properties;
    }

    /** @return Icon[]|null */
    private function icons(Wire $owner): ?array
    {
        $icons = $owner->optionalChildren('icons');

        return is_null($icons) ? null : array_map(fn(Wire $icon) => new Icon(
            $icon->string('src'),
            $icon->optionalString('mimeType'),
            $icon->optionalList('sizes'),
            $icon->optionalEnum('theme', IconTheme::class),
        ), $icons);
    }

    private function implementation(Wire $info): Implementation
    {
        return new Implementation(
            $info->string('name'),
            $info->string('version'),
            $this->icons($info),
            $info->optionalString('description'),
            $info->optionalString('websiteUrl'),
            $info->optionalString('title'),
        );
    }

    private function reference(Wire $ref): PromptReference|ResourceTemplateReference
    {
        return match($ref->string('type')) {
            'ref/prompt' => new PromptReference($ref->string('name'), $ref->optionalString('title')),
            'ref/resource' => new ResourceTemplateReference($ref->string('uri')),
            default => throw $ref->invalid('type', '"ref/prompt" or "ref/resource"'),
        };
    }

    private function annotations(Wire $owner): ?Annotations
    {
        $annotations = $owner->optionalChild('annotations');
        if(is_null($annotations)) return null;

        $audience = $annotations->has('audience')
            ? array_map(
                fn(mixed $role) => Role::tryFrom(is_string($role) ? $role : '') ?? throw $annotations->invalid('audience', 'a list of "user" or "assistant"'),
                $annotations->list('audience'),
            )
            : null;

        return new Annotations($audience, $annotations->optionalNumber('priority'), $annotations->optionalString('lastModified'));
    }

    private function resourceContents(Wire $contents): TextResourceContents|BlobResourceContents
    {
        return $contents->has('blob')
            ? new BlobResourceContents($contents->string('blob'), $contents->string('uri'), $contents->optionalString('mimeType'), $this->metaObject($contents))
            : new TextResourceContents($contents->string('text'), $contents->string('uri'), $contents->optionalString('mimeType'), $this->metaObject($contents));
    }

    private function content(Wire $content): ContentBlock
    {
        return match($content->string('type')) {
            'text' => new TextContent($content->string('text'), $this->annotations($content), $this->metaObject($content)),
            'image' => new ImageContent($content->string('data'), $content->string('mimeType'), $this->annotations($content), $this->metaObject($content)),
            'audio' => new AudioContent($content->string('data'), $content->string('mimeType'), $this->annotations($content), $this->metaObject($content)),
            'resource' => new EmbeddedResource($this->resourceContents($content->child('resource')), $this->annotations($content), $this->metaObject($content)),
            'resource_link' => new ResourceLink(
                $content->string('uri'),
                $content->string('name'),
                $content->optionalString('description'),
                $content->optionalString('mimeType'),
                $this->annotations($content),
                $content->optionalInt('size'),
                $this->icons($content),
                $content->optionalString('title'),
                $this->metaObject($content),
            ),
            default => throw $content->invalid('type', '"text", "image", "audio", "resource" or "resource_link"'),
        };
    }

    private function samplingContent(Wire $content): SamplingMessageContentBlock
    {
        return match($content->string('type')) {
            'text', 'image', 'audio' => $this->content($content),
            'tool_use' => new ToolUseContent($content->string('id'), $content->string('name'), $content->object('input'), $this->metaObject($content)),
            'tool_result' => new ToolResultContent(
                $content->string('toolUseId'),
                array_map($this->content(...), $content->children('content')),
                $content->has('structuredContent') ? $content->data['structuredContent'] : null,
                $content->optionalBool('isError'),
                $this->metaObject($content),
            ),
            default => throw $content->invalid('type', '"text", "image", "audio", "tool_use" or "tool_result"'),
        };
    }

    /**
     * A sampling content member is one block or a list of them.
     *
     * @return SamplingMessageContentBlock|list<SamplingMessageContentBlock>
     */
    private function samplingContents(Wire $owner, string $key): SamplingMessageContentBlock|array
    {
        $value = $owner->data[$key] ?? null;

        return is_array($value) && array_is_list($value)
            ? array_map($this->samplingContent(...), $owner->children($key))
            : $this->samplingContent($owner->child($key));
    }

    private function samplingMessage(Wire $message): SamplingMessage
    {
        return new SamplingMessage($message->enum('role', Role::class), $this->samplingContents($message, 'content'), $this->metaObject($message));
    }

    private function promptMessage(Wire $message): PromptMessage
    {
        return new PromptMessage($message->enum('role', Role::class), $this->content($message->child('content')));
    }

    private function modelPreferences(?Wire $preferences): ?ModelPreferences
    {
        if(is_null($preferences)) return null;

        $hints = $preferences->optionalChildren('hints');

        return new ModelPreferences(
            is_null($hints) ? null : array_map(fn(Wire $hint) => new ModelHint($hint->optionalString('name')), $hints),
            $preferences->optionalNumber('costPriority'),
            $preferences->optionalNumber('speedPriority'),
            $preferences->optionalNumber('intelligencePriority'),
        );
    }

    private function toolAnnotations(Wire $tool): ?ToolAnnotations
    {
        $annotations = $tool->optionalChild('annotations');
        if(is_null($annotations)) return null;

        return new ToolAnnotations(
            $annotations->optionalString('title'),
            $annotations->optionalBool('readOnlyHint'),
            $annotations->optionalBool('destructiveHint'),
            $annotations->optionalBool('idempotentHint'),
            $annotations->optionalBool('openWorldHint'),
        );
    }

    private function tool(Wire $tool): ToolDefinition
    {
        return new ToolDefinition(
            $tool->string('name'),
            $tool->optionalString('description'),
            $tool->object('inputSchema'),
            $tool->optionalObject('outputSchema'),
            $this->toolAnnotations($tool),
            $this->icons($tool),
            $tool->optionalString('title'),
            $this->metaObject($tool),
        );
    }

    private function prompt(Wire $prompt): PromptDefinition
    {
        $arguments = $prompt->optionalChildren('arguments');

        return new PromptDefinition(
            $prompt->string('name'),
            $prompt->optionalString('description'),
            is_null($arguments) ? null : array_map(
                fn(Wire $argument) => new PromptArgument($argument->string('name'), $argument->optionalString('description'), $argument->optionalBool('required'), $argument->optionalString('title')),
                $arguments,
            ),
            $this->icons($prompt),
            $prompt->optionalString('title'),
            $this->metaObject($prompt),
        );
    }

    private function resource(Wire $resource): ResourceDefinition
    {
        return new ResourceDefinition(
            $resource->string('uri'),
            $resource->string('name'),
            $resource->optionalString('description'),
            $resource->optionalString('mimeType'),
            $this->annotations($resource),
            $resource->optionalInt('size'),
            $this->icons($resource),
            $resource->optionalString('title'),
            $this->metaObject($resource),
        );
    }

    private function resourceTemplate(Wire $template): ResourceTemplateDefinition
    {
        return new ResourceTemplateDefinition(
            $template->string('uriTemplate'),
            $template->string('name'),
            $template->optionalString('description'),
            $template->optionalString('mimeType'),
            $this->annotations($template),
            $this->icons($template),
            $template->optionalString('title'),
            $this->metaObject($template),
        );
    }
}
