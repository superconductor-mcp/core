<?php

namespace Fabricate\Mechanism\Rpc\Procedures\Prompts;

use Closure;
use ReflectionClass;
use Throwable;
use Fabricate\NutsAndBolts\MCPServerBag;
use Fabricate\NutsAndBolts\PromptMessage;
use Fabricate\NutsAndBolts\PromptDefinition;
use Fabricate\NutsAndBolts\Attributes\PromptGet;
use Fabricate\Mechanism\Rpc\Procedures\Procedure;
use Fabricate\NutsAndBolts\Messages\Errors\InternalError;
use Fabricate\NutsAndBolts\Messages\Errors\InvalidParams;
use Fabricate\NutsAndBolts\Messages\Errors\JsonRpcError;
use Fabricate\NutsAndBolts\Messages\Prompts\GetPromptRequest;
use Fabricate\NutsAndBolts\Messages\Prompts\GetPromptResponse;

class GetPromptProcedure extends Procedure
{
    public const bool ASYNC = true;

    public function handle(GetPromptRequest $request, MCPServerBag $bag): GetPromptResponse|JsonRpcError
    {
        $definition = $this->offered($request->name, $bag);

        // the spec's "invalid prompt name": -32602
        if (is_null($definition)) {
            return new InvalidParams($request->id, "Unknown prompt: {$request->name}");
        }

        // the spec's "missing required arguments": -32602; an argument the prompt never declared is refused the same way
        foreach ($definition->arguments as $argument) {
            if ($argument->required && ! array_key_exists($argument->name, $request->arguments)) {
                return new InvalidParams($request->id, "Missing required argument: {$argument->name}");
            }
        }

        foreach (array_keys($request->arguments) as $name) {
            if (is_null($definition->argument($name))) {
                return new InvalidParams($request->id, "Unknown argument: {$name}");
            }
        }

        // a class-name action must be a #[PromptGet] class with a public handle()
        $action = $this->resolve($definition->action);

        if (is_null($action)) {
            return new InternalError($request->id, "Prompt [{$request->name}] has no action to build its messages.");
        }

        try {
            $result = $action(...$request->arguments);
        } catch (Throwable $e) {
            return new InternalError($request->id, $e->getMessage());
        }

        // outputs are validated too: only PromptMessage objects go back
        $messages = $result instanceof PromptMessage ? [$result] : $result;

        if (! is_array($messages) || ! array_is_list($messages) || $messages !== array_filter($messages, fn (mixed $message): bool => $message instanceof PromptMessage)) {
            return new InternalError($request->id, "Prompt [{$request->name}] must return a PromptMessage or a list of them.");
        }

        return new GetPromptResponse($request->id, $messages, $definition->description);
    }

    protected function offered(string $name, MCPServerBag $bag): ?PromptDefinition
    {
        foreach ($bag->server_configuration->prompts() as $prompt) {
            if ($prompt instanceof PromptDefinition && $prompt->name === $name) {
                return $prompt;
            }
        }

        return null;
    }

    // a closure is used as-is; a class name is built through the container only when it carries #[PromptGet]
    protected function resolve(Closure|string|null $action): ?Closure
    {
        if ($action instanceof Closure) {
            return $action;
        }

        if (is_null($action) || ! class_exists($action)) {
            return null;
        }

        $class = new ReflectionClass($action);

        if ($class->getAttributes(PromptGet::class) === [] || ! $class->hasMethod('handle') || ! $class->getMethod('handle')->isPublic()) {
            return null;
        }

        return app()->make($action)->handle(...);
    }
}
