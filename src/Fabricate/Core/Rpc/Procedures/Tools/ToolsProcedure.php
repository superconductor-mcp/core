<?php

// strict: a tool argument of the wrong JSON type is refused, not coerced into the constructor's type.
declare(strict_types=1);

namespace Fabricate\Core\Rpc\Procedures\Tools;

use Error;
use Fabricate\Contracts\Core\SuperconductorMCPException;
use Fabricate\Contracts\Mechanism\Attributes\CompatibleVersions;
use Fabricate\Contracts\Mechanism\Attributes\RequiresCapability;
use Fabricate\Contracts\Schema\Messaging\Inputs\InputRequiredResult;
use Fabricate\Contracts\Schema\Messaging\Tasks\CreateTaskResult;
use Fabricate\Contracts\Schema\Messaging\Tools\CallToolRequest;
use Fabricate\Contracts\Schema\Messaging\Tools\CallToolResult;
use Fabricate\Contracts\Schema\Messaging\Tools\ListToolsRequest;
use Fabricate\Contracts\Schema\Messaging\Tools\ListToolsResult;
use Fabricate\Contracts\Schema\RPCErrorCode;
use Fabricate\Contracts\Server\ServerConfiguration;
use Fabricate\Core\Rpc\Procedures\Procedure;
use Fabricate\Contracts\Schema\Messaging\MCPError;
use Fabricate\Mechanism\Completion;
use Fabricate\Mechanism\InputRequired;
use Fabricate\Mechanism\Deferred;
use Fabricate\Mechanism\Paginator;
use Fabricate\Mechanism\RequestContext;
use Fabricate\Mechanism\Tool;
use Fabricate\Schema\V20241105;
use Fabricate\Schema\V20250326;
use Fabricate\Schema\V20250618;
use Fabricate\Schema\V20251125;
use Fabricate\Schema\V20260728;
use Fabricate\Schema\V20260728\Messaging\MetaObjects\ResultMetaObject;
use Fabricate\Server\ToolRegistry;
use JsonException;
use Stringable;
use Throwable;

#[CompatibleVersions('2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25', '2026-07-28')]
#[RequiresCapability('tools')]
class ToolsProcedure extends Procedure
{
    public function __construct(
        protected ToolRegistry $tools,
    ) {}

    /**
     * The page of the server's tools the request's cursor points at, each defined in the requested version.
     */
    public function list(ListToolsRequest $request, ServerConfiguration $entity_configuration, string $protocol_version): ListToolsResult
    {
        [$names, $next_cursor] = Paginator::page(array_values($entity_configuration->tools()), $request->cursor(), (int) config('mcp.pagination.page_size', 50));

        $definitions = array_map(fn(string $name) => $this->definition($this->tools->tool($name), $protocol_version), $names);

        return match($protocol_version) {
            '2024-11-05' => new V20241105\Messaging\Tools\ListToolsResult($definitions, $next_cursor),
            '2025-03-26' => new V20250326\Messaging\Tools\ListToolsResult($definitions, $next_cursor),
            '2025-06-18' => new V20250618\Messaging\Tools\ListToolsResult($definitions, $next_cursor),
            '2025-11-25' => new V20251125\Messaging\Tools\ListToolsResult($definitions, $next_cursor),
            // Not cached: the list can change, and listChanged tells a session when it does.
            '2026-07-28' => new V20260728\Messaging\Tools\ListToolsResult($definitions, 0, V20260728\Concerns\CacheScope::PRIVATE, $next_cursor, _meta: $this->resultMeta($entity_configuration, $protocol_version)),
        };
    }

    /**
     * Builds the named tool from the request's arguments, runs its handle() and returns what it gives back as text content.
     * A failure inside the tool is a result with isError set, so the model can see it; an unknown tool is a protocol error.
     *
     * A tool that opts into async answers by mail when the call came in on a session. A call the client asked to run
     * as a task returns the task at once; the tool's outcome lands in the configuration's task store.
     */
    public function call(CallToolRequest $request, ServerConfiguration $entity_configuration, string $protocol_version, ?RequestContext $context = null): CallToolResult|CreateTaskResult|InputRequiredResult|Deferred
    {
        $name = $request->name();

        if(! in_array($name, $entity_configuration->tools(), true)) {
            throw new SuperconductorMCPException("Unknown tool: [{$name}].", RPCErrorCode::INVALID_PARAMS->value);
        }

        $class = $this->tools->tool($name);

        if(! method_exists($class, 'handle')) {
            throw new SuperconductorMCPException("Tool [{$name}] has no handle() method.", RPCErrorCode::INTERNAL_ERROR->value);
        }

        $meta = $this->resultMeta($entity_configuration, $protocol_version);
        $named = $this->retryInput($request, 'tools/call', $name);
        $as_task = $this->runsAsTask($request, $class, $entity_configuration, $protocol_version);
        $arguments = $request->arguments() ?? [];

        try {
            if($arguments !== [] && array_is_list($arguments)) throw new Error('arguments must be given by name.');

            $tool = new $class(...$arguments);
        } catch (Error $e) {
            $reason = $this->argumentError($e);
            $message = "Invalid arguments for tool [{$name}]: {$reason}";

            // Until 2025-06-18 invalid arguments are a protocol error; from 2025-11-25 they are a tool execution error the model can correct.
            if(in_array($protocol_version, ['2024-11-05', '2025-03-26', '2025-06-18'], true)) {
                throw new SuperconductorMCPException($message, RPCErrorCode::INVALID_PARAMS->value);
            }

            return $this->callResult($protocol_version, [$message], true, meta: $meta);
        }

        $build = fn(mixed $output) => $output instanceof InputRequired
            ? $this->laterInputRequired($output, 'tools/call', $name, $context, $entity_configuration, $protocol_version)
            : $this->outputResult($class, $output, $protocol_version, $meta);
        $fail = fn(Throwable $e) => $this->callResult($protocol_version, [$e->getMessage()], true, meta: $meta);

        if($as_task)
        {
            $task = $entity_configuration->taskStore()->create($request->taskTtl());

            $this->run($tool, $entity_configuration, $protocol_version, $context, $named, new Completion($build, $fail, function(CallToolResult $result) use ($task, $context) {
                $task->settle($result);
                $context?->notify($task->statusNotification());
            }));

            return new V20251125\Messaging\Tasks\CreateTaskResult($task->toSchema());
        }

        if($tool->wantsAsync() && isset($context))
        {
            $this->run($tool, $entity_configuration, $protocol_version, $context, $named, new Completion($build, $fail, fn(CallToolResult|InputRequiredResult|MCPError $answer) => $context->reply($answer)));

            return new Deferred();
        }

        try {
            $output = $this->callWith($tool, 'handle', $entity_configuration, $protocol_version, $named, $context);
        } catch (Throwable $e) {
            return $fail($e);
        }

        return $output instanceof InputRequired
            ? $this->inputRequired($output, 'tools/call', $name, $context, $entity_configuration, $protocol_version)
            : $this->outputResult($class, $output, $protocol_version, $meta);
    }

    /**
     * Runs a tool towards its completion: async when it opts in and there's a session to post back to, otherwise right here.
     */
    /**
     * @param array<string, mixed> $named What handle() gets by name besides the usual: a retry's input.
     */
    private function run(Tool $tool, ServerConfiguration $entity_configuration, string $protocol_version, ?RequestContext $context, array $named, Completion $completion): void
    {
        $async = $tool->wantsAsync() && isset($context);

        if($async) $tool->deferTo($completion);

        try {
            $output = $this->callWith($tool, 'handle', $entity_configuration, $protocol_version, $named, $context);

            if(! $async) $completion->finish($output);
        } catch (Throwable $e) {
            $completion->fail($e);
        }
    }

    /**
     * Whether the call runs as a task: the client asked, the server offers tasks for tools/call, and the tool allows it.
     * A tool that forbids tasks refuses a task request, and one that requires them refuses a plain call, with -32601.
     *
     * @param class-string<Tool> $tool
     */
    private function runsAsTask(CallToolRequest $request, string $tool, ServerConfiguration $entity_configuration, string $protocol_version): bool
    {
        // A receiver without the capability processes the request normally, ignoring the task metadata.
        if($protocol_version !== '2025-11-25' || ! $entity_configuration->capabilities($protocol_version)->offersCapability('tasks.requests.tools.call')) {
            return false;
        }

        $support = $tool::taskSupport();

        if($request->runsAsTask() && $support === Tool::TASKS_FORBIDDEN) {
            throw new SuperconductorMCPException("Tool [{$tool::name()}] can't run as a task.", RPCErrorCode::METHOD_NOT_FOUND->value);
        }

        if(! $request->runsAsTask() && $support === Tool::TASKS_REQUIRED) {
            throw new SuperconductorMCPException("Tool [{$tool::name()}] only runs as a task.", RPCErrorCode::METHOD_NOT_FOUND->value);
        }

        return $request->runsAsTask();
    }

    /**
     * A tool as the requested version defines it: its input schema, and from 2025-03-26 its annotations, from
     * 2025-06-18 its title and output schema, from 2025-11-25 its icons and whether it runs as a task.
     *
     * @param class-string<Tool> $tool
     */
    private function definition(string $tool, string $protocol_version): object
    {
        ['properties' => $properties, 'required' => $required] = $tool::inputSchema();
        $output = $tool::outputSchema();
        $hints = $tool::annotations();
        $annotations = is_null($hints) || $protocol_version === '2024-11-05'
            ? null
            : new ($this->schema($protocol_version, 'Capabilities\\Tools\\ToolAnnotations'))($hints->title, $hints->readOnlyHint, $hints->destructiveHint, $hints->idempotentHint, $hints->openWorldHint);

        return match($protocol_version) {
            '2024-11-05' => new V20241105\Capabilities\Tools\ToolDefinition($tool::name(), $tool::description(), $properties, $required),
            '2025-03-26' => new V20250326\Capabilities\Tools\ToolDefinition($tool::name(), $tool::description(), $properties, $required, $annotations),
            '2025-06-18' => new V20250618\Capabilities\Tools\ToolDefinition($tool::name(), $tool::description(), $properties, $required, ! is_null($output), $output['properties'] ?? null, $output['required'] ?? null, $annotations, $tool::title()),
            '2025-11-25' => new V20251125\Capabilities\Tools\ToolDefinition(
                $tool::name(), $tool::description(), $properties, $required,
                has_output_schema: ! is_null($output), output_properties: $output['properties'] ?? null, output_required: $output['required'] ?? null,
                execution: $tool::taskSupport() === Tool::TASKS_FORBIDDEN ? null : new V20251125\Capabilities\Tools\ToolExecution(V20251125\Concerns\TaskSupport::from($tool::taskSupport())),
                annotations: $annotations, icons: $this->icons($tool), title: $tool::title(),
            ),
            '2026-07-28' => new V20260728\Capabilities\Tools\ToolDefinition($tool::name(), $tool::description(), ['properties' => $properties, 'required' => $required]),
        };
    }

    /**
     * What a tool's handle() returned, as the version's CallToolResult. A tool with an output schema returns an
     * associative array: from 2025-06-18 it goes out as structuredContent, and in every version serialized as text.
     *
     * @param class-string<Tool> $tool
     */
    private function outputResult(string $tool, mixed $output, string $protocol_version, ?ResultMetaObject $meta = null): CallToolResult
    {
        if(! is_null($tool::outputSchema()) && is_array($output) && ! array_is_list($output))
        {
            $structured = in_array($protocol_version, ['2024-11-05', '2025-03-26'], true) ? null : $output;

            return $this->callResult($protocol_version, [json_encode($output, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)], structured: $structured, meta: $meta);
        }

        return $this->callResult($protocol_version, $this->contents($output, $protocol_version), meta: $meta);
    }

    /**
     * Each item a tool returned as content: the version's own content objects (text, image, audio, embedded resource,
     * and from 2025-06-18 resource links) pass through, strings and scalars are text, and anything else is its JSON.
     *
     * @return list<string|object>
     * @throws JsonException
     */
    private function contents(mixed $output, string $protocol_version): array
    {
        if(is_null($output)) return [];

        $items = is_array($output) && array_is_list($output) ? $output : [$output];
        $namespace = 'Fabricate\\Schema\\V'.str_replace('-', '', $protocol_version).'\\Concerns\\';

        return array_map(fn(mixed $item) => match(true) {
            is_string($item) => $item,
            is_object($item) && str_starts_with($item::class, $namespace) && in_array(class_basename($item), ['TextContent', 'ImageContent', 'AudioContent', 'EmbeddedResource', 'ResourceLink'], true) => $item,
            is_scalar($item), $item instanceof Stringable => (string) $item,
            default => json_encode($item, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
        }, $items);
    }

    /**
     * @param list<string|object> $contents Strings become the version's TextContent; content objects go as they are.
     * @param array<string, mixed>|null $structured
     */
    private function callResult(string $protocol_version, array $contents, bool $is_error = false, ?array $structured = null, ?ResultMetaObject $meta = null): CallToolResult
    {
        $is_error = $is_error ?: null;
        $text = $this->schema($protocol_version, 'Concerns\\TextContent');
        $content = array_map(fn(string|object $item) => is_string($item) ? new $text($item) : $item, $contents);

        return match($protocol_version) {
            '2024-11-05' => new V20241105\Messaging\Tools\CallToolResult($content, $is_error),
            '2025-03-26' => new V20250326\Messaging\Tools\CallToolResult($content, $is_error),
            '2025-06-18' => new V20250618\Messaging\Tools\CallToolResult($content, $structured, $is_error),
            '2025-11-25' => new V20251125\Messaging\Tools\CallToolResult($content, $structured, $is_error),
            '2026-07-28' => new V20260728\Messaging\Tools\CallToolResult($content, $structured, $is_error, _meta: $meta),
        };
    }
}
