<?php

// strict: a prompt argument that isn't a string is refused, not coerced.
declare(strict_types=1);

namespace Fabricate\Core\Rpc\Procedures\Prompts;

use Error;
use Fabricate\Contracts\Core\SuperconductorMCPException;
use Fabricate\Contracts\Mechanism\Attributes\CompatibleVersions;
use Fabricate\Contracts\Mechanism\Attributes\RequiresCapability;
use Fabricate\Contracts\Schema\Concerns\Role;
use Fabricate\Contracts\Schema\Messaging\Inputs\InputRequiredResult;
use Fabricate\Contracts\Schema\Messaging\Prompts\GetPromptRequest;
use Fabricate\Contracts\Schema\Messaging\Prompts\GetPromptResult;
use Fabricate\Contracts\Schema\Messaging\Prompts\ListPromptsRequest;
use Fabricate\Contracts\Schema\Messaging\Prompts\ListPromptsResult;
use Fabricate\Contracts\Schema\RPCErrorCode;
use Fabricate\Contracts\Server\ServerConfiguration;
use Fabricate\Core\Rpc\Procedures\Procedure;
use Fabricate\Contracts\Schema\Messaging\MCPError;
use Fabricate\Mechanism\Completion;
use Fabricate\Mechanism\Deferred;
use Fabricate\Mechanism\InputRequired;
use Fabricate\Mechanism\Paginator;
use Fabricate\Mechanism\RequestContext;
use Throwable;
use Fabricate\Schema\V20241105;
use Fabricate\Schema\V20250326;
use Fabricate\Schema\V20250618;
use Fabricate\Schema\V20251125;
use Fabricate\Schema\V20260728;
use Fabricate\Schema\V20260728\Messaging\MetaObjects\ResultMetaObject;
use Fabricate\Server\PromptRegistry;

#[CompatibleVersions('2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25', '2026-07-28')]
#[RequiresCapability('prompts')]
class PromptsProcedure extends Procedure
{
    public function __construct(
        protected PromptRegistry $prompts,
    ) {}

    /**
     * The page of the server's prompts the request's cursor points at, each defined in the requested version.
     */
    public function list(ListPromptsRequest $request, ServerConfiguration $entity_configuration, string $protocol_version): ListPromptsResult
    {
        [$names, $next_cursor] = Paginator::page(array_values($entity_configuration->prompts()), $request->cursor(), (int) config('mcp.pagination.page_size', 50));

        $definitions = array_map(fn(string $name) => $this->definition($this->prompts->prompt($name), $protocol_version), $names);

        return match($protocol_version) {
            '2024-11-05' => new V20241105\Messaging\Prompts\ListPromptsResult($definitions, $next_cursor),
            '2025-03-26' => new V20250326\Messaging\Prompts\ListPromptsResult($definitions, $next_cursor),
            '2025-06-18' => new V20250618\Messaging\Prompts\ListPromptsResult($definitions, $next_cursor),
            '2025-11-25' => new V20251125\Messaging\Prompts\ListPromptsResult($definitions, $next_cursor),
            // Not cached: the list can change, and listChanged tells a session when it does.
            '2026-07-28' => new V20260728\Messaging\Prompts\ListPromptsResult($definitions, 0, V20260728\Concerns\CacheScope::PRIVATE, $next_cursor, _meta: $this->resultMeta($entity_configuration, $protocol_version)),
        };
    }

    /**
     * Builds the named prompt from the request's arguments and returns what its handle() gives back as messages.
     * An unknown prompt and missing or unknown arguments are -32602 in every version.
     */
    public function get(GetPromptRequest $request, ServerConfiguration $entity_configuration, string $protocol_version, ?RequestContext $context = null): GetPromptResult|InputRequiredResult|Deferred
    {
        $name = $request->name();

        if(! in_array($name, $entity_configuration->prompts(), true)) {
            throw new SuperconductorMCPException("Unknown prompt: [{$name}].", RPCErrorCode::INVALID_PARAMS->value);
        }

        $class = $this->prompts->prompt($name);

        if(! method_exists($class, 'handle')) {
            throw new SuperconductorMCPException("Prompt [{$name}] has no handle() method.", RPCErrorCode::INTERNAL_ERROR->value);
        }

        $arguments = $request->arguments() ?? [];
        $named = $this->retryInput($request, 'prompts/get', $name);

        try {
            if($arguments !== [] && array_is_list($arguments)) throw new Error('arguments must be given by name.');

            $prompt = new $class(...$arguments);
        } catch (Error $e) {
            $reason = $this->argumentError($e);

            throw new SuperconductorMCPException("Invalid arguments for prompt [{$name}]: {$reason}", RPCErrorCode::INVALID_PARAMS->value);
        }

        if($prompt->wantsAsync() && isset($context))
        {
            $completion = new Completion(
                fn(mixed $output) => $output instanceof InputRequired
                    ? $this->laterInputRequired($output, 'prompts/get', $name, $context, $entity_configuration, $protocol_version)
                    : $this->getResult($output, $class, $name, $protocol_version, $this->resultMeta($entity_configuration, $protocol_version)),
                fn(Throwable $e) => new ($this->schema($protocol_version, 'Messaging\\MCPError'))(RPCErrorCode::INTERNAL_ERROR->value, "Prompt [{$name}] failed: {$e->getMessage()}"),
                fn(GetPromptResult|InputRequiredResult|MCPError $answer) => $context->reply($answer),
            );
            $prompt->deferTo($completion);

            try {
                $this->callWith($prompt, 'handle', $entity_configuration, $protocol_version, $named, $context);
            } catch(Throwable $e) {
                $completion->fail($e);
            }

            return new Deferred();
        }

        $output = $this->callWith($prompt, 'handle', $entity_configuration, $protocol_version, $named, $context);

        return $output instanceof InputRequired
            ? $this->inputRequired($output, 'prompts/get', $name, $context, $entity_configuration, $protocol_version)
            : $this->getResult($output, $class, $name, $protocol_version, $this->resultMeta($entity_configuration, $protocol_version));
    }

    /**
     * What a prompt's handle() returned, as the version's GetPromptResult.
     *
     * @param class-string<\Fabricate\Mechanism\Prompt> $class
     */
    private function getResult(mixed $output, string $class, string $name, string $protocol_version, ?ResultMetaObject $meta = null): GetPromptResult
    {
        $messages = array_map(fn(mixed $item) => $this->message($item, $name, $protocol_version), is_array($output) && array_is_list($output) ? $output : [$output]);

        return match($protocol_version) {
            '2024-11-05' => new V20241105\Messaging\Prompts\GetPromptResult($messages, $class::description()),
            '2025-03-26' => new V20250326\Messaging\Prompts\GetPromptResult($messages, $class::description()),
            '2025-06-18' => new V20250618\Messaging\Prompts\GetPromptResult($messages, $class::description()),
            '2025-11-25' => new V20251125\Messaging\Prompts\GetPromptResult($messages, $class::description()),
            '2026-07-28' => new V20260728\Messaging\Prompts\GetPromptResult($messages, $class::description(), _meta: $meta),
        };
    }

    /**
     * @param class-string<\Fabricate\Mechanism\Prompt> $prompt
     */
    private function definition(string $prompt, string $protocol_version): object
    {
        $argument = $this->schema($protocol_version, 'Capabilities\\Prompts\\PromptArgument');
        $titled = ! in_array($protocol_version, ['2024-11-05', '2025-03-26'], true);
        $arguments = array_map(fn(array $a) => $titled
            ? new $argument($a['name'], $a['description'], $a['required'], $a['title'])
            : new $argument($a['name'], $a['description'], $a['required']), $prompt::arguments());

        return match($protocol_version) {
            '2024-11-05' => new V20241105\Capabilities\Prompts\PromptDefinition($prompt::name(), $prompt::description(), $arguments),
            '2025-03-26' => new V20250326\Capabilities\Prompts\PromptDefinition($prompt::name(), $prompt::description(), $arguments),
            '2025-06-18' => new V20250618\Capabilities\Prompts\PromptDefinition($prompt::name(), $prompt::description(), $arguments, $prompt::title()),
            '2025-11-25' => new V20251125\Capabilities\Prompts\PromptDefinition($prompt::name(), $prompt::description(), $arguments, $this->icons($prompt), $prompt::title()),
            '2026-07-28' => new V20260728\Capabilities\Prompts\PromptDefinition($prompt::name(), $prompt::description(), $arguments, $this->icons($prompt), $prompt::title()),
        };
    }

    /**
     * One item a prompt's handle() returned, as a message: a string is the user's text, and the version's own PromptMessage passes through.
     */
    private function message(mixed $item, string $name, string $protocol_version): object
    {
        $message = match(true) {
            ! is_string($item) => $item,
            $protocol_version === '2024-11-05' => new V20241105\Capabilities\Prompts\PromptMessage(Role::USER, new V20241105\Concerns\TextContent($item)),
            $protocol_version === '2025-03-26' => new V20250326\Capabilities\Prompts\PromptMessage(Role::USER, new V20250326\Concerns\TextContent($item)),
            $protocol_version === '2025-06-18' => new V20250618\Capabilities\Prompts\PromptMessage(Role::USER, new V20250618\Concerns\TextContent($item)),
            $protocol_version === '2025-11-25' => new V20251125\Capabilities\Prompts\PromptMessage(Role::USER, new V20251125\Concerns\TextContent($item)),
            $protocol_version === '2026-07-28' => new V20260728\Capabilities\Prompts\PromptMessage(Role::USER, new V20260728\Concerns\TextContent($item)),
        };

        $expected = 'Fabricate\\Schema\\V'.str_replace('-', '', $protocol_version).'\\Capabilities\\Prompts\\PromptMessage';

        if(! $message instanceof $expected) {
            throw new SuperconductorMCPException("Prompt [{$name}] returned ".get_debug_type($item).": handle() must return a string, a {$protocol_version} PromptMessage, or a list of them.", RPCErrorCode::INTERNAL_ERROR->value);
        }

        return $message;
    }
}
