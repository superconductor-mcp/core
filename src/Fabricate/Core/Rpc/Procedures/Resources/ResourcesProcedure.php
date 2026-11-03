<?php

namespace Fabricate\Core\Rpc\Procedures\Resources;

use Fabricate\Contracts\Core\SuperconductorMCPException;
use Fabricate\Contracts\Mechanism\Attributes\CompatibleVersions;
use Fabricate\Contracts\Mechanism\Attributes\RequiresCapability;
use Fabricate\Contracts\Schema\Messaging\EmptyResult;
use Fabricate\Contracts\Schema\Messaging\Inputs\InputRequiredResult;
use Fabricate\Contracts\Schema\Messaging\MCPError;
use Fabricate\Contracts\Schema\Messaging\Resources\ListResourcesRequest;
use Fabricate\Contracts\Schema\Messaging\Resources\ListResourcesResult;
use Fabricate\Contracts\Schema\Messaging\Resources\ListResourceTemplatesRequest;
use Fabricate\Contracts\Schema\Messaging\Resources\ListResourceTemplatesResult;
use Fabricate\Contracts\Schema\Messaging\Resources\ReadResourceRequest;
use Fabricate\Contracts\Schema\Messaging\Resources\ReadResourceResult;
use Fabricate\Contracts\Schema\Messaging\Resources\SubscribeRequest;
use Fabricate\Contracts\Schema\Messaging\Resources\UnsubscribeRequest;
use Fabricate\Contracts\Schema\ResourceErrorCode;
use Fabricate\Contracts\Schema\RPCErrorCode;
use Fabricate\Contracts\Server\ServerConfiguration;
use Fabricate\Core\Rpc\Procedures\Procedure;
use Fabricate\Mechanism\Completion;
use Fabricate\Mechanism\Deferred;
use Fabricate\Mechanism\InputRequired;
use Fabricate\Mechanism\Paginator;
use Fabricate\Mechanism\RequestContext;
use Fabricate\Mechanism\Resource;
use Fabricate\Mechanism\ResourceTemplate;
use Fabricate\Schema\Common\Messaging\EmptyResult as SchemaEmptyResult;
use Fabricate\Schema\V20260728\Concerns\CacheScope;
use Fabricate\Schema\V20260728\Messaging\MetaObjects\ResultMetaObject;
use Fabricate\Server\ResourceRegistry;
use Fabricate\Server\ResourceTemplateRegistry;
use Throwable;

#[RequiresCapability('resources')]
class ResourcesProcedure extends Procedure
{
    public function __construct(
        protected ResourceRegistry $resources,
        protected ResourceTemplateRegistry $templates,
    ) {}

    /**
     * The page of the server's fixed-URI resources the request's cursor points at.
     */
    #[CompatibleVersions('2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25', '2026-07-28')]
    public function list(ListResourcesRequest $request, ServerConfiguration $entity_configuration, string $protocol_version): ListResourcesResult
    {
        [$uris, $next_cursor] = Paginator::page($this->offered($entity_configuration)['resources'], $request->cursor(), (int) config('mcp.pagination.page_size', 50));

        $definition = $this->schema($protocol_version, 'Capabilities\\Resources\\ResourceDefinition');
        $resources = array_map(function(string $uri) use ($definition, $protocol_version) {
            $resource = $this->resources->resource($uri);

            return new $definition(...['uri' => $uri, 'name' => $resource::name(), 'description' => $resource::description(), 'mimeType' => $resource::mimeType(), 'size' => $resource::size()], ...$this->described($resource, $protocol_version));
        }, $uris);

        return $this->listResult($protocol_version, 'ListResourcesResult', $resources, $next_cursor, $this->resultMeta($entity_configuration, $protocol_version));
    }

    /**
     * The page of the server's resource templates the request's cursor points at.
     */
    #[CompatibleVersions('2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25', '2026-07-28')]
    public function listTemplates(ListResourceTemplatesRequest $request, ServerConfiguration $entity_configuration, string $protocol_version): ListResourceTemplatesResult
    {
        [$uri_templates, $next_cursor] = Paginator::page($this->offered($entity_configuration)['templates'], $request->cursor(), (int) config('mcp.pagination.page_size', 50));

        $definition = $this->schema($protocol_version, 'Capabilities\\Resources\\ResourceTemplateDefinition');
        $templates = array_map(function(string $uri_template) use ($definition, $protocol_version) {
            $template = $this->templates->template($uri_template);

            return new $definition(...['uriTemplate' => $uri_template, 'name' => $template::name(), 'description' => $template::description(), 'mimeType' => $template::mimeType()], ...$this->described($template, $protocol_version));
        }, $uri_templates);

        return $this->listResult($protocol_version, 'ListResourceTemplatesResult', $templates, $next_cursor, $this->resultMeta($entity_configuration, $protocol_version));
    }

    /**
     * Reads the resource at the request's URI: the fixed-URI resource with that URI, or the first template it fits.
     * A resource that opts into async gets a Completion and answers by mail.
     */
    #[CompatibleVersions('2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25', '2026-07-28')]
    public function read(ReadResourceRequest $request, ServerConfiguration $entity_configuration, string $protocol_version, ?RequestContext $context = null): ReadResourceResult|InputRequiredResult|Deferred
    {
        $uri = $request->uri();
        [$class, $variables] = $this->resolve($uri, $entity_configuration, $protocol_version);

        $readable = app($class);

        if(! method_exists($readable, 'read')) {
            throw new SuperconductorMCPException("Resource [{$uri}] has no read() method.", RPCErrorCode::INTERNAL_ERROR->value);
        }

        $named = ['uri' => $uri] + $variables + $this->retryInput($request, 'resources/read', $uri);

        if($readable->wantsAsync() && isset($context))
        {
            $completion = new Completion(
                fn(mixed $output) => $output instanceof InputRequired
                    ? $this->laterInputRequired($output, 'resources/read', $uri, $context, $entity_configuration, $protocol_version)
                    : $this->readResult($protocol_version, $uri, $class::mimeType(), $output, $this->resultMeta($entity_configuration, $protocol_version)),
                fn(Throwable $e) => new ($this->schema($protocol_version, 'Messaging\\MCPError'))(RPCErrorCode::INTERNAL_ERROR->value, "Reading [{$uri}] failed: {$e->getMessage()}"),
                fn(ReadResourceResult|InputRequiredResult|MCPError $answer) => $context->reply($answer),
            );
            $readable->deferTo($completion);

            try {
                $this->callWith($readable, 'read', $entity_configuration, $protocol_version, $named, $context);
            } catch(Throwable $e) {
                $completion->fail($e);
            }

            return new Deferred();
        }

        $output = $this->callWith($readable, 'read', $entity_configuration, $protocol_version, $named, $context);

        return $output instanceof InputRequired
            ? $this->inputRequired($output, 'resources/read', $uri, $context, $entity_configuration, $protocol_version)
            : $this->readResult($protocol_version, $uri, $class::mimeType(), $output, $this->resultMeta($entity_configuration, $protocol_version));
    }

    /**
     * Records that the session wants notifications/resources/updated for a URI it could read.
     */
    #[CompatibleVersions('2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25')]
    #[RequiresCapability('resources.subscribe')]
    public function subscribe(SubscribeRequest $request, ServerConfiguration $entity_configuration, string $protocol_version): EmptyResult
    {
        $this->resolve($request->uri(), $entity_configuration, $protocol_version);
        $entity_configuration->subscribe($request->uri());

        return new SchemaEmptyResult();
    }

    #[CompatibleVersions('2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25')]
    #[RequiresCapability('resources.subscribe')]
    public function unsubscribe(UnsubscribeRequest $request, ServerConfiguration $entity_configuration, string $protocol_version): EmptyResult
    {
        $entity_configuration->unsubscribe($request->uri());

        return new SchemaEmptyResult();
    }

    /**
     * The server's resources() split into fixed URIs and URI templates. A name in neither registry is a configuration mistake.
     *
     * @return array{resources: list<string>, templates: list<string>}
     */
    private function offered(ServerConfiguration $entity_configuration): array
    {
        $offered = ['resources' => [], 'templates' => []];

        foreach($entity_configuration->resources() as $name)
        {
            match(true) {
                $this->resources->has($name) => $offered['resources'][] = $name,
                $this->templates->has($name) => $offered['templates'][] = $name,
                default => throw new SuperconductorMCPException("Unknown resource [{$name}].", RPCErrorCode::INTERNAL_ERROR->value),
            };
        }

        return $offered;
    }

    /**
     * A resource's or template's annotations, title and icons, as the named arguments its definition takes in the
     * requested version: 2024-11-05 carries audience and priority itself, later versions an annotations object, then
     * a title from 2025-06-18 and icons from 2025-11-25.
     *
     * @param class-string<Resource|ResourceTemplate> $class
     * @return array<string, mixed>
     */
    private function described(string $class, string $protocol_version): array
    {
        $annotations = $class::annotations();

        $described = match($protocol_version) {
            '2024-11-05' => ['audience' => $annotations?->audience, 'priority' => $annotations?->priority],
            '2025-03-26' => ['annotations' => $this->resourceAnnotations($annotations, $protocol_version)],
            '2025-06-18' => ['annotations' => $this->resourceAnnotations($annotations, $protocol_version), 'title' => $class::title()],
            '2025-11-25', '2026-07-28' => ['annotations' => $this->resourceAnnotations($annotations, $protocol_version), 'icons' => $this->icons($class), 'title' => $class::title()],
        };

        return array_filter($described, fn(mixed $value) => ! is_null($value));
    }

    /**
     * The class that reads a URI and the template variables it fills; a URI the server can't read is -32002, or -32602 from 2026-07-28.
     *
     * @return array{0: class-string<Resource|ResourceTemplate>, 1: array<string, string>}
     */
    private function resolve(string $uri, ServerConfiguration $entity_configuration, string $protocol_version): array
    {
        $offered = $this->offered($entity_configuration);

        if(in_array($uri, $offered['resources'], true)) return [$this->resources->resource($uri), []];

        foreach($offered['templates'] as $uri_template)
        {
            $template = $this->templates->template($uri_template);

            if(! is_null($variables = $template::match($uri))) return [$template, $variables];
        }

        $code = $protocol_version === '2026-07-28' ? RPCErrorCode::INVALID_PARAMS : ResourceErrorCode::RESOURCE_NOT_FOUND;

        throw new SuperconductorMCPException("Resource not found: [{$uri}].", $code->value, data: ['uri' => $uri]);
    }

    /**
     * What a read() returned as the version's ReadResourceResult: a string is the resource's text, and the version's
     * own contents objects pass through; a list may mix both.
     */
    private function readResult(string $protocol_version, string $uri, ?string $mime_type, mixed $output, ?ResultMetaObject $meta = null): ReadResourceResult
    {
        $text = $this->schema($protocol_version, 'Capabilities\\Resources\\TextResourceContents');
        $contents = $this->schema($protocol_version, 'Capabilities\\Resources\\ResourceContents');

        $items = array_map(fn(mixed $item) => match(true) {
            is_string($item) => new $text(text: $item, uri: $uri, mimeType: $mime_type),
            $item instanceof $contents => $item,
            default => throw new SuperconductorMCPException("Resource [{$uri}] returned ".get_debug_type($item).": read() must return a string, {$protocol_version} resource contents, or a list of them.", RPCErrorCode::INTERNAL_ERROR->value),
        }, is_array($output) && array_is_list($output) ? $output : [$output]);

        $result = $this->schema($protocol_version, 'Messaging\\Resources\\ReadResourceResult');

        return $protocol_version === '2026-07-28'
            ? new $result($items, 0, CacheScope::PRIVATE, _meta: $meta)
            : new $result($items);
    }

    /**
     * @param list<object> $items
     */
    private function listResult(string $protocol_version, string $result, array $items, mixed $next_cursor, ?ResultMetaObject $meta = null): object
    {
        $class = $this->schema($protocol_version, "Messaging\\Resources\\{$result}");

        // Not cached on 2026-07-28: the list can change, and a list_changed notification says when it does.
        return $protocol_version === '2026-07-28'
            ? new $class($items, 0, CacheScope::PRIVATE, $next_cursor, _meta: $meta)
            : new $class($items, $next_cursor);
    }
}
