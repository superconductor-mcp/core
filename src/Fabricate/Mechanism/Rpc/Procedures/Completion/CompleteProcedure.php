<?php

namespace Fabricate\Mechanism\Rpc\Procedures\Completion;

use Closure;
use ReflectionClass;
use Throwable;
use Fabricate\NutsAndBolts\MCPServerBag;
use Fabricate\NutsAndBolts\CompletionResult;
use Fabricate\NutsAndBolts\ResourceTemplateDefinition;
use Fabricate\NutsAndBolts\PromptDefinition;
use Fabricate\NutsAndBolts\Attributes\Completes;
use Fabricate\Mechanism\Rpc\Procedures\Procedure;
use Fabricate\NutsAndBolts\Messages\Errors\InternalError;
use Fabricate\NutsAndBolts\Messages\Errors\InvalidParams;
use Fabricate\NutsAndBolts\Messages\Errors\InvalidRequest;
use Fabricate\NutsAndBolts\Messages\Errors\JsonRpcError;
use Fabricate\NutsAndBolts\Messages\Completion\CompleteRequest;
use Fabricate\NutsAndBolts\Messages\Completion\CompleteResponse;

class CompleteProcedure extends Procedure
{
    public const bool ASYNC = true;

    public function handle(CompleteRequest $request, MCPServerBag $bag): CompleteResponse|JsonRpcError
    {
        // the spec makes rate limiting a MUST for completion; the limit is per session
        if (! $this->withinRateLimit($bag)) {
            return new InvalidRequest($request->id, 'Too many completion requests; slow down.');
        }

        if ($request->ref_type === 'ref/prompt') {
            $prompt = $this->prompt($request->ref, $bag);

            if (is_null($prompt)) {
                return new InvalidParams($request->id, "Unknown prompt: {$request->ref}");
            }

            $argument = $prompt->argument($request->argument_name);

            if (is_null($argument)) {
                return new InvalidParams($request->id, "Prompt [{$request->ref}] has no argument [{$request->argument_name}].");
            }

            $completer = $argument->completer;
        } else {
            $template = $this->template($request->ref, $bag);

            if (is_null($template)) {
                return new InvalidParams($request->id, "Unknown resource template: {$request->ref}");
            }

            if (! in_array($request->argument_name, $template->variables(), true)) {
                return new InvalidParams($request->id, "Template [{$request->ref}] has no argument [{$request->argument_name}].");
            }

            $completer = $template->completers[$request->argument_name] ?? null;
        }

        // no completer for this argument: an empty answer is still a valid one
        $completer = $this->resolve($completer);

        if (is_null($completer)) {
            return new CompleteResponse($request->id);
        }

        try {
            $result = $completer($request->argument_value);
        } catch (Throwable $e) {
            return new InternalError($request->id, $e->getMessage());
        }

        if (is_array($result)) {
            try {
                $result = new CompletionResult($result);
            } catch (Throwable $e) {
                return new InternalError($request->id, $e->getMessage());
            }
        }

        if (! $result instanceof CompletionResult) {
            return new InternalError($request->id, 'A completer must return a CompletionResult or a list of strings.');
        }

        return new CompleteResponse($request->id, $result);
    }

    protected function withinRateLimit(MCPServerBag $bag): bool
    {
        $now = microtime(true);
        $window = (float) config('mcp.completion.rate_limit.per_seconds', 1);
        $max = (int) config('mcp.completion.rate_limit.max', 10);

        $bag->completion_calls = array_values(array_filter($bag->completion_calls, fn (float $at): bool => $at > $now - $window));

        if (count($bag->completion_calls) >= $max) {
            return false;
        }

        $bag->completion_calls[] = $now;

        return true;
    }

    protected function prompt(string $name, MCPServerBag $bag): ?PromptDefinition
    {
        foreach ($bag->server_configuration->prompts() as $prompt) {
            if ($prompt instanceof PromptDefinition && $prompt->name === $name) {
                return $prompt;
            }
        }

        return null;
    }

    protected function template(string $uri_template, MCPServerBag $bag): ?ResourceTemplateDefinition
    {
        foreach ($bag->server_configuration->resourceTemplates() as $template) {
            if ($template instanceof ResourceTemplateDefinition && $template->uri_template === $uri_template) {
                return $template;
            }
        }

        return null;
    }

    // a closure is used as-is; a class name is built through the container only when it carries #[Completes]
    protected function resolve(Closure|string|null $completer): ?Closure
    {
        if ($completer instanceof Closure) {
            return $completer;
        }

        if (is_null($completer) || ! class_exists($completer)) {
            return null;
        }

        $class = new ReflectionClass($completer);

        if ($class->getAttributes(Completes::class) === [] || ! $class->hasMethod('handle') || ! $class->getMethod('handle')->isPublic()) {
            return null;
        }

        return app()->make($completer)->handle(...);
    }
}
