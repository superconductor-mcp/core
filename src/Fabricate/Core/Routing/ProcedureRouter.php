<?php

namespace Fabricate\Core\Routing;

use Fabricate\Mechanism\Deferred;
use Fabricate\Mechanism\RequestContext;
use Closure;
use Throwable;
use ReflectionMethod;
use ReflectionFunction;
use ReflectionException;
use ReflectionFunctionAbstract;
use Fabricate\Contracts\Schema\RPCErrorCode;
use Fabricate\Contracts\Schema\Messaging as Messages;
use Fabricate\Contracts\Schema\Messaging\MCPError;
use Fabricate\Contracts\Schema\Messaging\Request;
use Fabricate\Contracts\Schema\Messaging\Result;
use Fabricate\Contracts\Schema\Messaging\RPC\JSONRPCEnvelope;
use Fabricate\Contracts\Schema\Messaging\Tasks\CreateTaskResult;
use Fabricate\Server\ServerConfiguration;
use Fabricate\Contracts\Core\SuperconductorMCPException;
use Fabricate\Contracts\Mechanism\EntityCapabilities;
use Fabricate\Contracts\Mechanism\EntityConfiguration;
use Fabricate\Contracts\Mechanism\Attributes\CompatibleVersions;
use Fabricate\Contracts\Mechanism\Attributes\RequiresCapability;
use Voyager\Contracts\IOPools\CancelledException;

class ProcedureRouter
{
    /** What each spec method may answer with, so a route an app replaces can't answer another method's result. */
    private array $method_results = [
        'ping' => [Messages\EmptyResult::class],
        'server/discover' => [Messages\Discover\DiscoverResult::class],
        'tools/list' => [Messages\Tools\ListToolsResult::class],
        'tools/call' => [Messages\Tools\CallToolResult::class, CreateTaskResult::class, Messages\Inputs\InputRequiredResult::class],
        'prompts/list' => [Messages\Prompts\ListPromptsResult::class],
        'prompts/get' => [Messages\Prompts\GetPromptResult::class, Messages\Inputs\InputRequiredResult::class],
        'resources/list' => [Messages\Resources\ListResourcesResult::class],
        'resources/templates/list' => [Messages\Resources\ListResourceTemplatesResult::class],
        'resources/read' => [Messages\Resources\ReadResourceResult::class, Messages\Inputs\InputRequiredResult::class],
        'resources/subscribe' => [Messages\EmptyResult::class],
        'resources/unsubscribe' => [Messages\EmptyResult::class],
        'subscriptions/listen' => [Messages\Resources\SubscriptionsListenResult::class],
        'completion/complete' => [Messages\Autocomplete\CompleteResult::class],
        'logging/setLevel' => [Messages\EmptyResult::class],
        'tasks/get' => [Messages\Tasks\GetTaskResult::class],
        'tasks/result' => [Messages\Tasks\GetTaskPayloadResult::class],
        'tasks/list' => [Messages\Tasks\ListTasksResult::class],
        // 2026-07-28's tasks extension acknowledges tasks/cancel and tasks/update with an empty result.
        'tasks/cancel' => [Messages\Tasks\CancelTaskResult::class, Messages\EmptyResult::class],
        'tasks/update' => [Messages\EmptyResult::class],
        'sampling/createMessage' => [Messages\Sampling\CreateMessageResult::class, CreateTaskResult::class],
        'roots/list' => [Messages\Roots\ListRootsResult::class],
        'elicitation/create' => [Messages\Elicitation\ElicitResult::class, CreateTaskResult::class],
    ];

    /** A receiver that doesn't declare these must handle the request normally, ignoring its task metadata. */
    private array $task_capabilities = [
        'tools/call' => 'tasks.requests.tools.call',
        'sampling/createMessage' => 'tasks.requests.sampling.createMessage',
        'elicitation/create' => 'tasks.requests.elicitation.create',
    ];

    protected array $procedures = [];
    protected string $prefix = '';

    public function group(string $name, callable $action): static
    {
        $previous = $this->prefix;
        $this->prefix = "{$previous}{$name}/";

        try {
            $action();
        } finally {
            $this->prefix = $previous;
        }

        return $this;
    }

    public function procedure(string $name, string|callable $action): static
    {
        $this->procedures["{$this->prefix}{$name}"] = $action;

        return $this;
    }

    public function dispatch(string $protocol_version, Request $request, EntityConfiguration $entity_configuration, ?RequestContext $context = null): Result|MCPError|Deferred
    {
        // Runs first so an unsupported version is reported as that, whichever method it came with.
        $capabilities = $entity_configuration->capabilities($protocol_version);

        if($request instanceof JSONRPCEnvelope) $request = $request->request;

        $method = $request->method;

        if(!isset($this->procedures[$method])) {
            $this->refuse("No procedure for {$method}");
        }

        $action = $this->procedures[$method];
        $target = match(true) {
            is_string($action) => explode('@', $action) + [1 => 'handle'],
            is_object($action) && !$action instanceof Closure => [$action, '__invoke'],
            default => $action,
        };

        try {
            $function = $target instanceof Closure ? new ReflectionFunction($target) : new ReflectionMethod(...$target);
        } catch(ReflectionException $e) {
            throw new SuperconductorMCPException("The route for {$method} is broken: {$e->getMessage()}", RPCErrorCode::INTERNAL_ERROR->value, $e);
        }

        $compatible = $this->attribute($function, CompatibleVersions::class)?->versions ?? [];

        if(!in_array($protocol_version, $compatible, true)) {
            $this->refuse("{$method} is not available in protocol version {$protocol_version}");
        }

        if(str_starts_with($request::class, 'Fabricate\\Schema\\V') && !str_starts_with($request::class, $this->versionRoot($protocol_version))) {
            $this->refuse($request::class." is not a {$protocol_version} request", RPCErrorCode::INVALID_REQUEST);
        }

        if(!$entity_configuration->acceptsRequest($request)) {
            $this->refuse($entity_configuration::class." can't receive {$method}");
        }

        $capability = $this->attribute($function, RequiresCapability::class)?->capability;

        if(isset($capability) && !$capabilities->offersCapability($capability)) {
            $this->refuse($entity_configuration::class." doesn't offer {$capability} for {$method}");
        }

        try {
            if(is_array($target) && is_string($target[0])) $target[0] = app($target[0]);

            // A procedure that answers async declares a fourth parameter for the request's context; the rest ignore it.
            $response = $target($request, $entity_configuration, $protocol_version, $context);
        } catch(CancelledException $e) {
            // The event loop's, not the procedure's: the fiber has to unwind.
            throw $e;
        } catch(Throwable $e) {
            return $this->errorFrom($e, $protocol_version);
        }

        // The answer comes later as mail; the procedure that deferred checks it when it builds it.
        if($response instanceof Deferred) return $response;

        if($this->answers($method, $response, $request, $capabilities, $protocol_version)) return $response;

        return $this->errorFrom(
            new SuperconductorMCPException("{$method} returned ".get_debug_type($response)." for protocol version {$protocol_version}"),
            $protocol_version,
        );
    }

    private function answers(string $method, mixed $response, Request $request, EntityCapabilities $capabilities, string $protocol_version): bool
    {
        if(!$this->belongsToVersion($response, $protocol_version)) return false;

        if($response instanceof CreateTaskResult && !$this->ranAsTask($method, $request, $capabilities, $protocol_version)) return false;

        if(!isset($this->method_results[$method])) return true;

        return array_any($this->method_results[$method], fn(string $result) => $response instanceof $result);
    }

    /**
     * Whether a task handle is a right answer: on 2025-11-25 the request asked for a task the capabilities offer it for;
     * on 2026-07-28 the server decides, but only if it offers the tasks extension.
     */
    private function ranAsTask(string $method, Request $request, EntityCapabilities $capabilities, string $protocol_version): bool
    {
        if($protocol_version === '2026-07-28') {
            return $method === 'tools/call' && isset($capabilities->extensions[ServerConfiguration::TASKS_EXTENSION]);
        }

        return isset($request->params->task, $this->task_capabilities[$method])
            && $capabilities->offersCapability($this->task_capabilities[$method]);
    }

    private function belongsToVersion(mixed $response, string $protocol_version): bool
    {
        if(!$response instanceof Result) return false;

        $version_root = $this->versionRoot($protocol_version);
        $versioned = str_replace('Fabricate\\Schema\\Common\\', $version_root, $response::class);

        // A Common result is only right for a version that doesn't define its own, as 2026-07-28 does for EmptyResult.
        return str_starts_with($versioned, $version_root) && ($versioned === $response::class || !class_exists($versioned));
    }

    /**
     * A SuperconductorMCPException with a code this version knows is a deliberate answer. Anything else is a fault:
     * it's reported, and the client only learns that something went wrong.
     */
    private function errorFrom(Throwable $e, string $protocol_version): MCPError
    {
        $error = "{$this->versionRoot($protocol_version)}Messaging\\MCPError";

        if($e instanceof SuperconductorMCPException) {
            try {
                return new $error($e->getCode(), $e->getMessage(), $e->data);
            } catch(SuperconductorMCPException) {
                // Not a code this version has, so it falls through as a fault.
            }
        }

        report($e);

        return new $error(RPCErrorCode::INTERNAL_ERROR->value, 'Internal error');
    }

    private function versionRoot(string $protocol_version): string
    {
        return 'Fabricate\\Schema\\V'.str_replace('-', '', $protocol_version).'\\';
    }

    /**
     * @template T of object
     * @param class-string<T> $attribute
     * @return T|null
     */
    private function attribute(ReflectionFunctionAbstract $function, string $attribute): ?object
    {
        $found = $function->getAttributes($attribute);

        if(empty($found) && $function instanceof ReflectionMethod) {
            $found = $function->getDeclaringClass()->getAttributes($attribute);
        }

        return empty($found) ? null : $found[0]->newInstance();
    }

    private function refuse(string $message, RPCErrorCode $code = RPCErrorCode::METHOD_NOT_FOUND): never
    {
        throw new SuperconductorMCPException($message, $code->value);
    }
}
