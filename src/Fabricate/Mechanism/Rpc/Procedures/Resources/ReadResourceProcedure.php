<?php

namespace Fabricate\Mechanism\Rpc\Procedures\Resources;

use Closure;
use ReflectionClass;
use Throwable;
use Fabricate\Mechanism\Session\UrlElicitationRequired;
use Fabricate\NutsAndBolts\MCPServerBag;
use Fabricate\NutsAndBolts\ResourceDefinition;
use Fabricate\NutsAndBolts\ResourceTemplateDefinition;
use Fabricate\NutsAndBolts\Attributes\ResourceRead;
use Fabricate\NutsAndBolts\Schema\ResourceContents;
use Fabricate\Mechanism\Rpc\Procedures\Procedure;
use Fabricate\NutsAndBolts\Messages\Errors\InternalError;
use Fabricate\NutsAndBolts\Messages\Errors\JsonRpcError;
use Fabricate\NutsAndBolts\Messages\Errors\ResourceNotFound;
use Fabricate\NutsAndBolts\Messages\Resources\ReadResourceRequest;
use Fabricate\NutsAndBolts\Messages\Resources\ReadResourceResponse;

class ReadResourceProcedure extends Procedure
{
    public const bool ASYNC = true;

    public function handle(ReadResourceRequest $request, MCPServerBag $bag): ReadResourceResponse|JsonRpcError
    {
        // a fixed resource with this exact uri wins; otherwise the first template the uri fits
        [$definition, $variables] = $this->find($request->uri, $bag);

        if (is_null($definition)) {
            return new ResourceNotFound($request->id, $request->uri);
        }

        // a class-name action must be a #[ResourceRead] class with a public handle()
        $action = $this->resolve($definition->action);

        if (is_null($action)) {
            return new InternalError($request->id, "Resource [{$request->uri}] has no readable action.");
        }

        try {
            // template variables arrive by name, the way tool arguments do; a fixed resource gets none
            $result = $action(...$variables);
        } catch (UrlElicitationRequired $e) {
            return $e->toError($request->id, $bag);
        } catch (Throwable $e) {
            return new InternalError($request->id, $e->getMessage());
        }

        $contents = $result instanceof ResourceContents ? [$result] : $result;

        if (! is_array($contents) || ! array_is_list($contents) || $contents !== array_filter($contents, fn (mixed $item): bool => $item instanceof ResourceContents)) {
            return new InternalError($request->id, "Resource [{$request->uri}] must return ResourceContents or a list of them.");
        }

        return new ReadResourceResponse($request->id, $contents);
    }

    /** @return array{0: ResourceDefinition|ResourceTemplateDefinition|null, 1: array<string, string>} */
    protected function find(string $uri, MCPServerBag $bag): array
    {
        foreach ($bag->server_configuration->resources() as $resource) {
            if ($resource instanceof ResourceDefinition && $resource->uri === $uri) {
                return [$resource, []];
            }
        }

        foreach ($bag->server_configuration->resourceTemplates() as $template) {
            if ($template instanceof ResourceTemplateDefinition && ! is_null($variables = $template->match($uri))) {
                return [$template, $variables];
            }
        }

        return [null, []];
    }

    // a closure is used as-is; a class name is built through the container only when it carries #[ResourceRead]
    protected function resolve(Closure|string|null $action): ?Closure
    {
        if ($action instanceof Closure) {
            return $action;
        }

        if (is_null($action) || ! class_exists($action)) {
            return null;
        }

        $class = new ReflectionClass($action);

        if ($class->getAttributes(ResourceRead::class) === [] || ! $class->hasMethod('handle') || ! $class->getMethod('handle')->isPublic()) {
            return null;
        }

        return app()->make($action)->handle(...);
    }
}
