<?php

namespace Fabricate\Mechanism\Rpc\Procedures\Tools;

use Closure;
use ReflectionClass;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use Throwable;
use Fabricate\Mechanism\Session\UrlElicitationRequired;
use Fabricate\NutsAndBolts\MCPServerBag;
use Fabricate\NutsAndBolts\ToolDefinition;
use Fabricate\NutsAndBolts\Attributes\ToolCall;
use Fabricate\Contracts\Mechanism\SchemaPrimitive;
use Fabricate\Mechanism\Rpc\Procedures\Procedure;
use Fabricate\NutsAndBolts\Messages\Errors\InvalidParams;
use Fabricate\NutsAndBolts\Messages\Errors\ToolCallError;
use Fabricate\NutsAndBolts\Schema\TextProperty;
use Fabricate\NutsAndBolts\Schema\StructuredContent;
use Fabricate\NutsAndBolts\Messages\Errors\JsonRpcError;
use Fabricate\NutsAndBolts\Messages\Errors\MethodNotFound;
use Fabricate\NutsAndBolts\Messages\Tools\CallToolRequest;
use Fabricate\NutsAndBolts\Messages\Tools\CallToolResponse;

class CallToolProcedure extends Procedure
{
    public const bool ASYNC = true;

    public function handle(CallToolRequest $request, MCPServerBag $bag): CallToolResponse|JsonRpcError
    {
        // 2. only a tool the server is offering right now can be called
        $definition = $this->offered($request->name, $bag);

        if (is_null($definition)) {
            return new ToolCallError($request->id, $request->name);
        }

        // 1. a class-name action must be a #[ToolCall] class with a handle() method
        $action = $this->resolve($definition->action);

        if (is_null($action)) {
            return new MethodNotFound($request->id, $request->name);
        }

        // 3. the request's arguments are injected by name into the tool's parameters
        if (! is_null($problem = $this->checkArguments($definition, $action, $request->arguments))) {
            // 2025-11-25 (SEP-1303): the model can read and correct a bad argument when it comes back as a tool result
            return ($bag->protocol_version ?? '') >= '2025-11-25'
                ? new CallToolResponse($request->id, [new TextProperty($problem)], true)
                : new InvalidParams($request->id, $problem);
        }

        try {
            $result = $action(...$request->arguments);
        } catch (UrlElicitationRequired $e) {
            return $e->toError($request->id, $bag);
        } catch (Throwable $e) {
            // the tool ran and failed: report it in the result, not as a JSON-RPC error
            return new CallToolResponse($request->id, [new TextProperty($e->getMessage())], true);
        }

        // 2025-06-18: a structured result carries its data beside the content
        $structured = null;

        if ($result instanceof StructuredContent) {
            $structured = $result->data;
            $result = $result->content();
        }

        // 4. a tool returns one schema primitive or a list of them; the response bundles them as content
        $content = $result instanceof SchemaPrimitive ? [$result] : $result;

        if (! is_array($content) || ! array_is_list($content) || $content !== array_filter($content, fn (mixed $item): bool => $item instanceof SchemaPrimitive)) {
            return new CallToolResponse(
                $request->id,
                [new TextProperty("Tool [{$request->name}] must return a schema primitive or a list of them.")],
                true,
            );
        }

        return new CallToolResponse($request->id, $content, structured: $structured);
    }

    protected function offered(string $name, MCPServerBag $bag): ?ToolDefinition
    {
        foreach ($bag->server_configuration->tools() as $tool) {
            if ($tool instanceof ToolDefinition && $tool->name === $name) {
                return $tool;
            }
        }

        return null;
    }

    // a closure is used as-is; a class name is built through the container only when it carries #[ToolCall]
    protected function resolve(Closure|string|null $action): ?Closure
    {
        if ($action instanceof Closure) {
            return $action;
        }

        if (is_null($action) || ! class_exists($action)) {
            return null;
        }

        $class = new ReflectionClass($action);

        if ($class->getAttributes(ToolCall::class) === [] || ! $class->hasMethod('handle') || ! $class->getMethod('handle')->isPublic()) {
            return null;
        }

        return app()->make($action)->handle(...);
    }

    // the same checks PHP makes when it spreads named arguments, turned into an InvalidParams instead of a crash
    protected function checkArguments(ToolDefinition $definition, Closure $action, array $arguments): ?string
    {
        foreach ($definition->required as $required) {
            if (! array_key_exists($required, $arguments)) {
                return "Missing required argument: {$required}";
            }
        }

        $parameters = $this->reflect($action)->getParameters();
        $variadic = array_filter($parameters, fn ($parameter) => $parameter->isVariadic()) !== [];
        $names = array_map(fn ($parameter) => $parameter->getName(), $parameters);

        foreach (array_keys($arguments) as $argument) {
            if (! $variadic && ! in_array($argument, $names, true)) {
                return "Unknown argument: {$argument}";
            }
        }

        foreach ($parameters as $parameter) {
            if (! $parameter->isOptional() && ! array_key_exists($parameter->getName(), $arguments)) {
                return "Missing required argument: {$parameter->getName()}";
            }
        }

        // a declared property's type: PHP would coerce the value (5 into a string parameter) or throw a TypeError
        foreach ($arguments as $name => $value) {
            if (isset($definition->properties[$name]) && ! is_null($problem = $definition->properties[$name]->problem($value))) {
                return "Invalid argument {$name}: {$problem}";
            }
        }

        return null;
    }

    protected function reflect(Closure $action): ReflectionFunctionAbstract
    {
        $function = new ReflectionFunction($action);

        // a first-class callable of handle() reflects as that method, so its parameter names are the tool's
        return is_null($function->getClosureThis()) || $function->getName() !== 'handle'
            ? $function
            : new ReflectionMethod($function->getClosureThis(), 'handle');
    }
}
