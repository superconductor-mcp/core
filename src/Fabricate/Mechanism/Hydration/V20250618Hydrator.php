<?php

namespace Fabricate\Mechanism\Hydration;

use Fabricate\Client\ClientCapabilitiesFactory;
use Fabricate\Contracts\Mechanism\VersionHydrator;
use Fabricate\Contracts\Schema\Concerns\Role;
use Fabricate\Contracts\Schema\ElicitAction;
use Fabricate\Contracts\Schema\IncludeContext;
use Fabricate\Contracts\Schema\LoggingLevel;
use Fabricate\Contracts\Schema\Messaging\MCPError;
use Fabricate\Contracts\Schema\Messaging\Notification;
use Fabricate\Contracts\Schema\Messaging\Request;
use Fabricate\Contracts\Schema\Messaging\Result;
use Fabricate\Contracts\Schema\ProgressToken;
use Fabricate\Contracts\Schema\RPCErrorCode;
use Fabricate\Mechanism\Decoding\DecodedError;
use Fabricate\Mechanism\Decoding\DecodedNotification;
use Fabricate\Mechanism\Decoding\DecodedRequest;
use Fabricate\Mechanism\Decoding\DecodedResponse;
use Fabricate\Schema\Common\Messaging\EmptyResult;
use Fabricate\Schema\V20250618\Capabilities\Elicitation\BooleanSchema;
use Fabricate\Schema\V20250618\Capabilities\Elicitation\EnumSchema;
use Fabricate\Schema\V20250618\Capabilities\Elicitation\NumberSchema;
use Fabricate\Schema\V20250618\Capabilities\Elicitation\StringSchema;
use Fabricate\Schema\V20250618\Capabilities\ClientCapabilities;
use Fabricate\Schema\V20250618\Capabilities\Prompts\PromptDefinition;
use Fabricate\Schema\V20250618\Capabilities\ServerCapabilities;
use Fabricate\Schema\V20250618\Capabilities\Prompts\PromptArgument;
use Fabricate\Schema\V20250618\Capabilities\Prompts\PromptMessage;
use Fabricate\Schema\V20250618\Capabilities\Resources\BlobResourceContents;
use Fabricate\Schema\V20250618\Capabilities\Resources\ResourceDefinition;
use Fabricate\Schema\V20250618\Capabilities\Resources\ResourceTemplateDefinition;
use Fabricate\Schema\V20250618\Capabilities\Resources\TextResourceContents;
use Fabricate\Schema\V20250618\Capabilities\Roots\Root;
use Fabricate\Schema\V20250618\Capabilities\Sampling\ModelHint;
use Fabricate\Schema\V20250618\Capabilities\Sampling\ModelPreferences;
use Fabricate\Schema\V20250618\Capabilities\Sampling\SamplingMessage;
use Fabricate\Schema\V20250618\Capabilities\Tools\ToolDefinition;
use Fabricate\Schema\V20250618\Capabilities\Tools\ToolAnnotations;
use Fabricate\Schema\V20250618\Concerns\Annotations;
use Fabricate\Schema\V20250618\Concerns\AudioContent;
use Fabricate\Schema\V20250618\Concerns\ContentBlock;
use Fabricate\Schema\V20250618\Concerns\EmbeddedResource;
use Fabricate\Schema\V20250618\Concerns\ImageContent;
use Fabricate\Schema\V20250618\Concerns\PrimitiveSchemaDefinition;
use Fabricate\Schema\V20250618\Concerns\PromptReference;
use Fabricate\Schema\V20250618\Concerns\ResourceLink;
use Fabricate\Schema\V20250618\Concerns\ResourceTemplateReference;
use Fabricate\Schema\V20250618\Concerns\TextContent;
use Fabricate\Schema\V20250618\Implementation;
use Fabricate\Schema\V20250618\Messaging;
use Fabricate\Schema\V20250618\Messaging\MCPError as VersionError;
use Fabricate\Server\ServerCapabilitiesFactory;

/** Builds the 2025-06-18 schema classes. Requests and notifications come back as the message itself; the id stays on the decoded message. */
final class V20250618Hydrator implements VersionHydrator
{
    private const string VERSION = '2025-06-18';

    public function request(DecodedRequest $message): Request
    {
        $params = new Wire($message->params ?? []);
        $meta = $params->meta();
        $token = $params->progressToken();

        return match($message->method) {
            'initialize' => new Messaging\Initialize\InitializeRequest(
                $this->namedVersion($params),
                $this->clientCapabilities($params->object('capabilities')),
                $this->implementation($params->child('clientInfo')),
                $meta,
                $token,
            ),
            'ping' => new Messaging\Ping\PingRequest($meta, $token),
            'tools/list' => new Messaging\Tools\ListToolsRequest($params->optionalCursor('cursor'), $meta, $token),
            'tools/call' => new Messaging\Tools\CallToolRequest($params->string('name'), $params->optionalObject('arguments'), $meta, $token),
            'prompts/list' => new Messaging\Prompts\ListPromptsRequest($params->optionalCursor('cursor'), $meta, $token),
            'prompts/get' => new Messaging\Prompts\GetPromptRequest($params->string('name'), $params->optionalObject('arguments'), $meta, $token),
            'resources/list' => new Messaging\Resources\ListResourcesRequest($params->optionalCursor('cursor'), $meta, $token),
            'resources/templates/list' => new Messaging\Resources\ListResourceTemplatesRequest($params->optionalCursor('cursor'), $meta, $token),
            'resources/read' => new Messaging\Resources\ReadResourceRequest($params->string('uri'), $meta, $token),
            'resources/subscribe' => new Messaging\Resources\SubscribeRequest($params->string('uri'), $meta, $token),
            'resources/unsubscribe' => new Messaging\Resources\UnsubscribeRequest($params->string('uri'), $meta, $token),
            'completion/complete' => new Messaging\Autocomplete\CompleteRequest(
                $this->reference($params->child('ref')),
                $params->child('argument')->string('name'),
                $params->child('argument')->string('value'),
                $params->optionalChild('context')?->optionalObject('arguments'),
                $meta,
                $token,
            ),
            'logging/setLevel' => new Messaging\Logging\SetLevelRequest($params->enum('level', LoggingLevel::class), $meta, $token),
            'roots/list' => new Messaging\Roots\ListRootsRequest($meta, $token),
            'sampling/createMessage' => new Messaging\Sampling\CreateMessageRequest(
                array_map($this->samplingMessage(...), $params->children('messages')),
                $params->int('maxTokens'),
                $this->modelPreferences($params->optionalChild('modelPreferences')),
                $params->optionalString('systemPrompt'),
                $params->optionalEnum('includeContext', IncludeContext::class),
                $params->optionalNumber('temperature'),
                $params->optionalList('stopSequences'),
                $params->optionalObject('metadata'),
                $meta,
                $token,
            ),
            'elicitation/create' => new Messaging\Elicitation\ElicitRequest(
                $params->string('message'),
                $this->elicitationProperties($params->child('requestedSchema')),
                $params->child('requestedSchema')->optionalList('required'),
                $meta,
                $token,
            ),
            default => throw $this->unknownMethod($message->method),
        };
    }

    public function notification(DecodedNotification $message): Notification
    {
        $params = new Wire($message->params ?? []);
        $meta = $params->optionalObject('_meta');

        return match($message->method) {
            'notifications/initialized' => new Messaging\Initialize\InitializedNotification(),
            'notifications/cancelled' => new Messaging\Cancellation\CancelledNotification($params->requestId('requestId'), $params->optionalString('reason'), $meta),
            'notifications/progress' => new Messaging\Progress\ProgressNotification(
                new ProgressToken($params->requestId('progressToken')->id),
                $params->number('progress'),
                $params->optionalNumber('total'),
                $params->optionalString('message'),
                $meta,
            ),
            'notifications/roots/list_changed' => new Messaging\Roots\RootsListChangedNotification($meta),
            'notifications/tools/list_changed' => new Messaging\Tools\ToolListChangedNotification($meta),
            'notifications/prompts/list_changed' => new Messaging\Prompts\PromptListChangedNotification($meta),
            'notifications/resources/list_changed' => new Messaging\Resources\ResourceListChangedNotification($meta),
            'notifications/resources/updated' => new Messaging\Resources\ResourceUpdatedNotification($params->string('uri'), $meta),
            'notifications/message' => new Messaging\Logging\LoggingMessageNotification(
                $params->enum('level', LoggingLevel::class),
                $params->has('data') ? $params->data['data'] : throw $params->invalid('data', 'present'),
                $params->optionalString('logger'),
                $meta,
            ),
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
                $this->samplingContent($result->child('content')),
                $result->optionalString('stopReason'),
                $meta,
            ),
            'elicitation/create' => new Messaging\Elicitation\ElicitResult($result->enum('action', ElicitAction::class), $result->optionalObject('content'), $meta),
            default => throw $this->unknownMethod($method),
        };
    }

    public function error(DecodedError $message): MCPError
    {
        if(is_null(RPCErrorCode::tryFrom($message->code)))
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

    private function implementation(Wire $info): Implementation
    {
        return new Implementation($info->string('name'), $info->string('version'), $info->optionalString('title'));
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

    private function samplingContent(Wire $content): TextContent|ImageContent|AudioContent
    {
        return match($content->string('type')) {
            'text' => new TextContent($content->string('text'), $this->annotations($content), $content->optionalObject('_meta')),
            'image' => new ImageContent($content->string('data'), $content->string('mimeType'), $this->annotations($content), $content->optionalObject('_meta')),
            'audio' => new AudioContent($content->string('data'), $content->string('mimeType'), $this->annotations($content), $content->optionalObject('_meta')),
            default => throw $content->invalid('type', '"text", "image" or "audio"'),
        };
    }

    private function content(Wire $content): ContentBlock
    {
        return match($content->optionalString('type')) {
            'resource' => new EmbeddedResource($this->resourceContents($content->child('resource')), $this->annotations($content), $content->optionalObject('_meta')),
            'resource_link' => new ResourceLink(
                $content->string('uri'),
                $content->string('name'),
                $content->optionalString('description'),
                $content->optionalString('mimeType'),
                $this->annotations($content),
                $content->optionalInt('size'),
                $content->optionalString('title'),
                $content->optionalObject('_meta'),
            ),
            default => $this->samplingContent($content),
        };
    }

    private function samplingMessage(Wire $message): SamplingMessage
    {
        return new SamplingMessage($message->enum('role', Role::class), $this->samplingContent($message->child('content')));
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

    /** @return array<string, PrimitiveSchemaDefinition> keyed by property name, as requestedSchema.properties is. */
    private function elicitationProperties(Wire $schema): array
    {
        $properties = [];
        foreach($schema->object('properties') as $name => $definition)
        {
            if(!is_array($definition)) throw new HydrationException("{$schema->path}.properties.{$name} must be an object.");

            $property = new Wire($definition, "{$schema->path}.properties.{$name}");
            $properties[$name] = match($property->string('type')) {
                'boolean' => new BooleanSchema($property->optionalString('title'), $property->optionalString('description'), $property->optionalBool('default')),
                'string' => $property->has('enum')
                    ? new EnumSchema($property->list('enum'), $property->optionalList('enumNames'), $property->optionalString('title'), $property->optionalString('description'))
                    : new StringSchema($property->optionalString('title'), $property->optionalString('description'), $property->optionalInt('minLength'), $property->optionalInt('maxLength'), $property->optionalString('format')),
                'number', 'integer' => new NumberSchema($property->string('type') === 'integer', $property->optionalString('title'), $property->optionalString('description'), $property->optionalNumber('minimum'), $property->optionalNumber('maximum')),
                default => throw $property->invalid('type', '"boolean", "string", "number" or "integer"'),
            };
        }

        return $properties;
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

        return new ToolDefinition(
            $tool->string('name'),
            $tool->optionalString('description'),
            $input->optionalObject('properties'),
            $input->optionalList('required'),
            !is_null($output),
            $output?->optionalObject('properties'),
            $output?->optionalList('required'),
            $this->toolAnnotations($tool),
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
            $template->optionalString('title'),
            $template->optionalObject('_meta'),
        );
    }
}
