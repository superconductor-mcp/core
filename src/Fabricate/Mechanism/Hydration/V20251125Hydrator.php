<?php

namespace Fabricate\Mechanism\Hydration;

use Fabricate\Client\ClientCapabilitiesFactory;
use Fabricate\Contracts\Mechanism\VersionHydrator;
use Fabricate\Contracts\Schema\Concerns\Role;
use Fabricate\Contracts\Schema\ElicitAction;
use Fabricate\Contracts\Schema\ElicitationErrorCode;
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
use Fabricate\Schema\Common\Messaging\EmptyResult;
use Fabricate\Schema\V20251125\Capabilities\Elicitation\BooleanSchema;
use Fabricate\Schema\V20251125\Capabilities\Elicitation\LegacyTitledEnumSchema;
use Fabricate\Schema\V20251125\Capabilities\Elicitation\NumberSchema;
use Fabricate\Schema\V20251125\Capabilities\Elicitation\StringSchema;
use Fabricate\Schema\V20251125\Capabilities\Elicitation\TitledMultiSelectEnumSchema;
use Fabricate\Schema\V20251125\Capabilities\Elicitation\TitledSingleSelectEnumSchema;
use Fabricate\Schema\V20251125\Capabilities\Elicitation\UntitledMultiSelectEnumSchema;
use Fabricate\Schema\V20251125\Capabilities\Elicitation\UntitledSingleSelectEnumSchema;
use Fabricate\Schema\V20251125\Capabilities\ClientCapabilities;
use Fabricate\Schema\V20251125\Capabilities\Prompts\PromptDefinition;
use Fabricate\Schema\V20251125\Capabilities\ServerCapabilities;
use Fabricate\Schema\V20251125\Capabilities\Prompts\PromptArgument;
use Fabricate\Schema\V20251125\Capabilities\Prompts\PromptMessage;
use Fabricate\Schema\V20251125\Capabilities\Resources\BlobResourceContents;
use Fabricate\Schema\V20251125\Capabilities\Resources\ResourceDefinition;
use Fabricate\Schema\V20251125\Capabilities\Resources\ResourceTemplateDefinition;
use Fabricate\Schema\V20251125\Capabilities\Resources\TextResourceContents;
use Fabricate\Schema\V20251125\Capabilities\Roots\Root;
use Fabricate\Schema\V20251125\Capabilities\Sampling\ModelHint;
use Fabricate\Schema\V20251125\Capabilities\Sampling\ModelPreferences;
use Fabricate\Schema\V20251125\Capabilities\Sampling\SamplingMessage;
use Fabricate\Schema\V20251125\Capabilities\Sampling\ToolChoice;
use Fabricate\Schema\V20251125\Capabilities\Tools\ToolDefinition;
use Fabricate\Schema\V20251125\Capabilities\Tools\ToolAnnotations;
use Fabricate\Schema\V20251125\Capabilities\Tools\ToolExecution;
use Fabricate\Schema\V20251125\Concerns\Annotations;
use Fabricate\Schema\V20251125\Concerns\AudioContent;
use Fabricate\Schema\V20251125\Concerns\ContentBlock;
use Fabricate\Schema\V20251125\Concerns\EmbeddedResource;
use Fabricate\Schema\V20251125\Concerns\ImageContent;
use Fabricate\Schema\V20251125\Concerns\PrimitiveSchemaDefinition;
use Fabricate\Schema\V20251125\Concerns\PromptReference;
use Fabricate\Schema\V20251125\Concerns\ResourceLink;
use Fabricate\Schema\V20251125\Concerns\ResourceTemplateReference;
use Fabricate\Schema\V20251125\Concerns\SamplingMessageContentBlock;
use Fabricate\Schema\V20251125\Concerns\TaskStatus;
use Fabricate\Schema\V20251125\Concerns\TaskSupport;
use Fabricate\Schema\V20251125\Concerns\TextContent;
use Fabricate\Schema\V20251125\Concerns\ToolResultContent;
use Fabricate\Schema\V20251125\Concerns\ToolUseContent;
use Fabricate\Schema\V20251125\Implementation;
use Fabricate\Schema\V20251125\Messaging;
use Fabricate\Schema\V20251125\Messaging\MCPError as VersionError;
use Fabricate\Schema\V20251125\Messaging\Params;
use Fabricate\Schema\V20251125\Task;
use Fabricate\Schema\V20251125\TaskMetadata;
use Fabricate\Server\ServerCapabilitiesFactory;

/** Builds the 2025-11-25 schema classes, whose requests and notifications are JSON-RPC messages themselves. */
final class V20251125Hydrator implements VersionHydrator
{
    private const string VERSION = '2025-11-25';

    public function request(DecodedRequest $message): Request
    {
        $params = new Wire($message->params ?? []);
        $meta = $params->meta();
        $token = $params->progressToken();
        $id = $message->id;

        return match($message->method) {
            'initialize' => new Messaging\Initialize\InitializeRequest($id, new Params\InitializeRequestParams(
                $this->namedVersion($params),
                $this->clientCapabilities($params->object('capabilities')),
                $this->implementation($params->child('clientInfo')),
                $meta,
                $token,
            )),
            'ping' => new Messaging\Ping\PingRequest($id, $this->requestParams($params)),
            'tools/list' => new Messaging\Tools\ListToolsRequest($id, $this->paginatedParams($params)),
            'tools/call' => new Messaging\Tools\CallToolRequest($id, new Params\CallToolRequestParams(
                $params->string('name'),
                $params->optionalObject('arguments'),
                $this->taskMetadata($params),
                $meta,
                $token,
            )),
            'prompts/list' => new Messaging\Prompts\ListPromptsRequest($id, $this->paginatedParams($params)),
            'prompts/get' => new Messaging\Prompts\GetPromptRequest($id, new Params\GetPromptRequestParams($params->string('name'), $params->optionalObject('arguments'), $meta, $token)),
            'resources/list' => new Messaging\Resources\ListResourcesRequest($id, $this->paginatedParams($params)),
            'resources/templates/list' => new Messaging\Resources\ListResourceTemplatesRequest($id, $this->paginatedParams($params)),
            'resources/read' => new Messaging\Resources\ReadResourceRequest($id, new Params\ReadResourceRequestParams($params->string('uri'), $meta, $token)),
            'resources/subscribe' => new Messaging\Resources\SubscribeRequest($id, new Params\SubscribeRequestParams($params->string('uri'), $meta, $token)),
            'resources/unsubscribe' => new Messaging\Resources\UnsubscribeRequest($id, new Params\UnsubscribeRequestParams($params->string('uri'), $meta, $token)),
            'completion/complete' => new Messaging\Autocomplete\CompleteRequest($id, new Params\CompleteRequestParams(
                $this->reference($params->child('ref')),
                $params->child('argument')->string('name'),
                $params->child('argument')->string('value'),
                $params->optionalChild('context')?->optionalObject('arguments'),
                $meta,
                $token,
            )),
            'logging/setLevel' => new Messaging\Logging\SetLevelRequest($id, new Params\SetLevelRequestParams($params->enum('level', LoggingLevel::class), $meta, $token)),
            'roots/list' => new Messaging\Roots\ListRootsRequest($id, $this->requestParams($params)),
            'sampling/createMessage' => new Messaging\Sampling\CreateMessageRequest($id, new Params\CreateMessageRequestParams(
                array_map($this->samplingMessage(...), $params->children('messages')),
                $params->int('maxTokens'),
                $this->modelPreferences($params->optionalChild('modelPreferences')),
                $params->optionalString('systemPrompt'),
                $params->optionalEnum('includeContext', IncludeContext::class),
                $params->optionalNumber('temperature'),
                $params->optionalList('stopSequences'),
                $params->optionalObject('metadata'),
                $params->has('tools') ? array_map($this->tool(...), $params->children('tools')) : null,
                $params->has('toolChoice') ? new ToolChoice($params->child('toolChoice')->optionalEnum('mode', ToolChoiceMode::class)) : null,
                $this->taskMetadata($params),
                $meta,
                $token,
            )),
            'elicitation/create' => new Messaging\Elicitation\ElicitRequest($id, $this->elicitParams($params, $meta, $token)),
            'tasks/get' => new Messaging\Tasks\GetTaskRequest($id, $params->string('taskId')),
            'tasks/result' => new Messaging\Tasks\GetTaskPayloadRequest($id, $params->string('taskId')),
            'tasks/cancel' => new Messaging\Tasks\CancelTaskRequest($id, $params->string('taskId')),
            'tasks/list' => new Messaging\Tasks\ListTasksRequest($id, $this->paginatedParams($params)),
            default => throw $this->unknownMethod($message->method),
        };
    }

    public function notification(DecodedNotification $message): Notification
    {
        $params = new Wire($message->params ?? []);
        $meta = $params->optionalObject('_meta');
        $plain = is_null($message->params) ? null : new Params\NotificationParams($meta);

        return match($message->method) {
            'notifications/initialized' => new Messaging\Initialize\InitializedNotification($plain),
            'notifications/cancelled' => new Messaging\Cancellation\CancelledNotification(new Params\CancelledNotificationParams($params->optionalRequestId('requestId'), $params->optionalString('reason'), $meta)),
            'notifications/progress' => new Messaging\Progress\ProgressNotification(new Params\ProgressNotificationParams(
                new ProgressToken($params->requestId('progressToken')->id),
                $params->number('progress'),
                $params->optionalNumber('total'),
                $params->optionalString('message'),
                $meta,
            )),
            'notifications/roots/list_changed' => new Messaging\Roots\RootsListChangedNotification($plain),
            'notifications/tools/list_changed' => new Messaging\Tools\ToolListChangedNotification($plain),
            'notifications/prompts/list_changed' => new Messaging\Prompts\PromptListChangedNotification($plain),
            'notifications/resources/list_changed' => new Messaging\Resources\ResourceListChangedNotification($plain),
            'notifications/resources/updated' => new Messaging\Resources\ResourceUpdatedNotification(new Params\ResourceUpdatedNotificationParams($params->string('uri'), $meta)),
            'notifications/message' => new Messaging\Logging\LoggingMessageNotification(new Params\LoggingMessageNotificationParams(
                $params->enum('level', LoggingLevel::class),
                $params->has('data') ? $params->data['data'] : throw $params->invalid('data', 'present'),
                $params->optionalString('logger'),
                $meta,
            )),
            'notifications/elicitation/complete' => new Messaging\Elicitation\ElicitationCompleteNotification($params->string('elicitationId')),
            'notifications/tasks/status' => new Messaging\Tasks\TaskStatusNotification(new Params\TaskStatusNotificationParams($this->task($params), $meta)),
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
        $meta = $result->optionalObject('_meta');

        // A request that ran as a task answers with the task instead of its own result.
        if($result->has('task') && in_array($method, ['tools/call', 'sampling/createMessage', 'elicitation/create']))
        {
            return new Messaging\Tasks\CreateTaskResult($this->task($result->child('task')), $meta);
        }

        return match($method) {
            'initialize' => new Messaging\Initialize\InitializeResult(
                $result->string('protocolVersion'),
                $this->serverCapabilities($result->object('capabilities')),
                $this->implementation($result->child('serverInfo')),
                $result->optionalString('instructions'),
                $meta,
            ),
            'ping', 'resources/subscribe', 'resources/unsubscribe', 'logging/setLevel' => new EmptyResult($meta),
            'tools/list' => new Messaging\Tools\ListToolsResult(array_map($this->tool(...), $result->children('tools')), $result->optionalCursor('nextCursor'), $meta),
            'tools/call' => new Messaging\Tools\CallToolResult(
                array_map($this->content(...), $result->children('content')),
                $result->optionalObject('structuredContent'),
                $result->optionalBool('isError'),
                $meta,
            ),
            'prompts/list' => new Messaging\Prompts\ListPromptsResult(array_map($this->prompt(...), $result->children('prompts')), $result->optionalCursor('nextCursor'), $meta),
            'prompts/get' => new Messaging\Prompts\GetPromptResult(array_map($this->promptMessage(...), $result->children('messages')), $result->optionalString('description'), $meta),
            'resources/list' => new Messaging\Resources\ListResourcesResult(array_map($this->resource(...), $result->children('resources')), $result->optionalCursor('nextCursor'), $meta),
            'resources/templates/list' => new Messaging\Resources\ListResourceTemplatesResult(array_map($this->resourceTemplate(...), $result->children('resourceTemplates')), $result->optionalCursor('nextCursor'), $meta),
            'resources/read' => new Messaging\Resources\ReadResourceResult(array_map($this->resourceContents(...), $result->children('contents')), $meta),
            'completion/complete' => new Messaging\Autocomplete\CompleteResult(
                $result->child('completion')->list('values'),
                $result->child('completion')->optionalInt('total'),
                $result->child('completion')->optionalBool('hasMore'),
                $meta,
            ),
            'roots/list' => new Messaging\Roots\ListRootsResult(
                array_map(fn(Wire $root) => new Root($root->string('uri'), $root->optionalString('name'), $root->optionalObject('_meta')), $result->children('roots')),
                $meta,
            ),
            'sampling/createMessage' => new Messaging\Sampling\CreateMessageResult(
                $result->string('model'),
                $result->enum('role', Role::class),
                $this->samplingContents($result, 'content'),
                $result->optionalString('stopReason'),
                $meta,
            ),
            'elicitation/create' => new Messaging\Elicitation\ElicitResult($result->enum('action', ElicitAction::class), $result->optionalObject('content'), $meta),
            'tasks/get' => new Messaging\Tasks\GetTaskResult($this->task($result), $meta),
            'tasks/cancel' => new Messaging\Tasks\CancelTaskResult($this->task($result), $meta),
            'tasks/list' => new Messaging\Tasks\ListTasksResult(array_map($this->task(...), $result->children('tasks')), $result->optionalCursor('nextCursor'), $meta),
            // The payload is the original request's result, kept as sent; its shape depends on that request.
            'tasks/result' => new Messaging\Tasks\GetTaskPayloadResult(array_diff_key($message->result, ['_meta' => true]), $meta),
            default => throw $this->unknownMethod($method),
        };
    }

    public function error(DecodedError $message): MCPError
    {
        if(is_null(RPCErrorCode::tryFrom($message->code)) && is_null(ElicitationErrorCode::tryFrom($message->code)))
        {
            throw new HydrationException("error.code [{$message->code}] is not a code this version knows.", RPCErrorCode::INVALID_REQUEST);
        }

        return new VersionError($message->code, $message->message, $message->data);
    }

    private function unknownMethod(string $method): HydrationException
    {
        return new HydrationException("Method not found: [{$method}].", RPCErrorCode::METHOD_NOT_FOUND);
    }

    /** The version an initialize request names; one naming another version was never meant for this hydrator. */
    private function namedVersion(Wire $params): string
    {
        $version = $params->string('protocolVersion');
        if($version !== self::VERSION) throw new NotThisVersion("params.protocolVersion [{$version}] is not a supported protocol version.");

        return $version;
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

    private function requestParams(Wire $params): ?Params\RequestParams
    {
        return $params->data === [] ? null : new Params\RequestParams($params->meta(), $params->progressToken());
    }

    private function paginatedParams(Wire $params): ?Params\PaginatedRequestParams
    {
        return $params->data === [] ? null : new Params\PaginatedRequestParams($params->optionalCursor('cursor'), $params->meta(), $params->progressToken());
    }

    private function taskMetadata(Wire $params): ?TaskMetadata
    {
        $task = $params->optionalChild('task');

        return is_null($task) ? null : new TaskMetadata($task->optionalInt('ttl'));
    }

    private function task(Wire $task): Task
    {
        return new Task(
            $task->string('taskId'),
            $task->enum('status', TaskStatus::class),
            $task->string('createdAt'),
            $task->string('lastUpdatedAt'),
            $task->has('ttl') && !is_null($task->data['ttl']) ? $task->int('ttl') : null,
            $task->optionalString('statusMessage'),
            $task->optionalInt('pollInterval'),
        );
    }

    /** @param array<string, mixed>|null $meta */
    private function elicitParams(Wire $params, ?array $meta, ?ProgressToken $token): Params\ElicitRequestFormParams|Params\ElicitRequestURLParams
    {
        return match($params->optionalString('mode') ?? 'form') {
            'form' => new Params\ElicitRequestFormParams(
                $params->string('message'),
                $this->elicitationProperties($params->child('requestedSchema')),
                $params->child('requestedSchema')->optionalList('required'),
                $params->child('requestedSchema')->optionalString('$schema'),
                $this->taskMetadata($params),
                $meta,
                $token,
            ),
            'url' => new Params\ElicitRequestURLParams(
                $params->string('message'),
                $params->string('elicitationId'),
                $params->string('url'),
                $this->taskMetadata($params),
                $meta,
                $token,
            ),
            default => throw $params->invalid('mode', '"form" or "url"'),
        };
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
            ? new BlobResourceContents($contents->string('blob'), $contents->string('uri'), $contents->optionalString('mimeType'), $contents->optionalObject('_meta'))
            : new TextResourceContents($contents->string('text'), $contents->string('uri'), $contents->optionalString('mimeType'), $contents->optionalObject('_meta'));
    }

    private function content(Wire $content): ContentBlock
    {
        return match($content->string('type')) {
            'text' => new TextContent($content->string('text'), $this->annotations($content), $content->optionalObject('_meta')),
            'image' => new ImageContent($content->string('data'), $content->string('mimeType'), $this->annotations($content), $content->optionalObject('_meta')),
            'audio' => new AudioContent($content->string('data'), $content->string('mimeType'), $this->annotations($content), $content->optionalObject('_meta')),
            'resource' => new EmbeddedResource($this->resourceContents($content->child('resource')), $this->annotations($content), $content->optionalObject('_meta')),
            'resource_link' => new ResourceLink(
                $content->string('uri'),
                $content->string('name'),
                $content->optionalString('description'),
                $content->optionalString('mimeType'),
                $this->annotations($content),
                $content->optionalInt('size'),
                $this->icons($content),
                $content->optionalString('title'),
                $content->optionalObject('_meta'),
            ),
            default => throw $content->invalid('type', '"text", "image", "audio", "resource" or "resource_link"'),
        };
    }

    private function samplingContent(Wire $content): SamplingMessageContentBlock
    {
        return match($content->string('type')) {
            'text', 'image', 'audio' => $this->content($content),
            'tool_use' => new ToolUseContent($content->string('id'), $content->string('name'), $content->object('input'), $content->optionalObject('_meta')),
            'tool_result' => new ToolResultContent(
                $content->string('toolUseId'),
                array_map($this->content(...), $content->children('content')),
                $content->optionalObject('structuredContent'),
                $content->optionalBool('isError'),
                $content->optionalObject('_meta'),
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
        return new SamplingMessage($message->enum('role', Role::class), $this->samplingContents($message, 'content'), $message->optionalObject('_meta'));
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
        $input = $tool->child('inputSchema');
        $output = $tool->optionalChild('outputSchema');
        $execution = $tool->optionalChild('execution');

        return new ToolDefinition(
            $tool->string('name'),
            $tool->optionalString('description'),
            $input->optionalObject('properties'),
            $input->optionalList('required'),
            $input->optionalString('$schema'),
            !is_null($output),
            $output?->optionalObject('properties'),
            $output?->optionalList('required'),
            $output?->optionalString('$schema'),
            is_null($execution) ? null : new ToolExecution($execution->optionalEnum('taskSupport', TaskSupport::class)),
            $this->toolAnnotations($tool),
            $this->icons($tool),
            $tool->optionalString('title'),
            $tool->optionalObject('_meta'),
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
            $prompt->optionalObject('_meta'),
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
            $resource->optionalObject('_meta'),
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
            $template->optionalObject('_meta'),
        );
    }
}
