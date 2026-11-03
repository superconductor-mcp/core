---
type: Module
title: Tools
description: How a server's tools are discovered, registered, defined from their class, listed with tools/list and run with tools/call.
resource: src/Fabricate/Server/ToolRegistry.php
tags: [tools, registry, discovery, pagination]
status: draft
generated: { by: claude-code/claude-opus-5-5, at: 2026-11-02T00:00:00Z }
sources:
  - id: tool
    resource: src/Fabricate/Mechanism/Tool.php
    title: Tool
  - id: registry
    resource: src/Fabricate/Server/ToolRegistry.php
    title: ToolRegistry
  - id: procedure
    resource: src/Fabricate/Core/Rpc/Procedures/Tools/ToolsProcedure.php
    title: ToolsProcedure
  - id: paginator
    resource: src/Fabricate/Mechanism/Paginator.php
    title: Paginator
  - id: tests
    resource: tests/Feature/Tools/ListToolsTest.php
    title: tools/list tests
  - id: call-tests
    resource: tests/Feature/Tools/CallToolTest.php
    title: tools/call tests
  - id: capability-tests
    resource: tests/Feature/Tools/CapabilityToolsTest.php
    title: Capability tool tests
  - id: task-tests
    resource: tests/Feature/Tasks/TasksTest.php
    title: Task and async call tests
  - id: previous
    resource: superconductor-mcp/core/src/Fabricate/Mechanism/Capabilities/Registries/ToolRegistry.php
    title: The previous version's ToolRegistry, which this design follows
---

# Defining a tool

A tool extends `Mechanism\Tool` and carries `#[ToolCall(name, description)]`. Its constructor is its input schema: each parameter is an argument, a parameter without a default is required, and `string`, `int`, `float`, `bool` and `array` map to `string`, `integer`, `number`, `boolean` and `object`. Any other type accepts any value.[^tool]

```php
#[ToolCall('shout', 'Send back the message in upper case.')]
class ShoutTool extends Tool
{
    public function __construct(public readonly string $message) {}
}
```

The class is the tool. The wire object `tools/list` returns is the version's `ToolDefinition`, built from it.

# Metadata and results

`#[ToolCall]` takes a `title`, sent from 2025-06-18. `#[ToolAnnotations]` gives the behaviour hints (`title`, `readOnlyHint`, `destructiveHint`, `idempotentHint`, `openWorldHint`), sent from 2025-03-26. Repeatable `#[Icon]`s are sent from 2025-11-25. An `#[Argument]` on a constructor parameter adds its `description` and `title` to the input schema. A tool that overrides `outputSchema()` is listed with it from 2025-06-18, and returns an associative array from `handle()`: that goes out as `structuredContent` from 2025-06-18, and serialized as text in every version.[^tool][^procedure]

`handle()` may return the version's own content objects (text, image, audio, embedded resource, and from 2025-06-18 resource links), alone or in a list with strings; they pass through as content.[^procedure]

# Registry and discovery

`ToolRegistry` refuses a tool whose name breaks the MCP tool name rules: 1 to 128 characters, each a letter, digit, underscore, hyphen or dot.[^registry]

`ToolRegistry`, bound as `mcp.tools`, maps each `#[ToolCall]` name to its class, and refuses a second class under a taken name. The provider registers every concrete tool under `mcp.tools.discovery_namespace`, `App\Mcp\Tools` by default. `tool($name)` returns the class: a tool is built per call from its arguments, so the registry never makes one.[^registry]

# tools/list

A server offers the tools whose names its `tools()` returns. `ToolsProcedure::list` pages those names, builds each tool's `ToolDefinition` in the requested version, and returns that version's `ListToolsResult`.[^procedure]

* Pages hold `mcp.pagination.page_size` names, 50 by default, behind an opaque cursor. A cursor this server didn't issue is refused with -32602.[^paginator][^tests]
* A name no tool is registered under is -32603 `Unknown tool [name].`, since it is a configuration mistake.[^tests]
* On 2026-07-28 the result is not cacheable: `ttlMs` 0, `cacheScope` private.[^procedure]

# tools/call

`ToolsProcedure::call` runs a tool the server offers:[^procedure][^call-tests]

1. A name not in the server's `tools()` is -32602 `Unknown tool: [name].`
2. The request's `arguments()` are passed to the tool's constructor by name, under strict types, so a wrong JSON type is refused, not coerced. Missing, unknown, mistyped or positional arguments are -32602 up to 2025-06-18; from 2025-11-25 they come back as a result with `isError`, which the spec moved to tool execution errors so the model can correct them.
3. `handle()` is called through the container. A parameter typed as the server configuration (the contract, the base class or the concrete) gets the configuration, and `$protocol_version` gets the version; anything else is resolved from the container.
4. What `handle()` returns becomes text content: nothing for null, one text per item of a list, a string or scalar as itself, anything else as JSON. An exception from the tool is a result with `isError` and its message.

# Async tools

A tool opts into async by setting `protected bool $async = true`. When the call came in on a session, `call` hands the tool a `Completion` and returns `Deferred`; `handle()` starts its work and returns, and later calls `$this->finish($output)` or `$this->fail($reason)`, which posts the answer by mail. Without a session to post to, `isAsync()` is false and `handle()` runs its sync path, so an async tool keeps one.[^procedure][^task-tests]

```php
public function handle(): ?string
{
    if(! $this->isAsync()) return self::ponder($this->message);

    app('concurrency')->driver('pool')->async(static fn() => self::ponder($message), 'ponder')
        ->then(fn(array $results) => $this->finish($results[0]), fn(Throwable $e) => $this->fail($e));

    return null;
}
```

# Tasks

`protected static string $task_support` says whether a 2025-11-25 client may run the tool as a task: `forbidden` (the default), `optional` or `required`, and `tools/list` advertises it as `execution.taskSupport`. A call with task metadata on a server that offers `tasks.requests.tools.call` returns `CreateTaskResult` at once; a sync tool's task is already completed, an async one's is working until it finishes. A task request to a tool that forbids it, or a plain call to one that requires it, is -32601. The task lives in [Tasks](/mechanism/tasks.md).[^task-tests]

# Capability tools

Core registers eight tools in every server's registry; a server offers one by naming it in `tools()`. `list_tools`, `search_tools`, `search_prompts`, `list_resources`, `search_resources`, `list_resource_templates` and `search_resource_templates` return what the server offers as JSON, the search ones matching a query against any text field. `execute_tool` runs another offered tool by name; it is async, and when the tool it runs is too, that tool's outcome becomes its own without holding the loop.[^capability-tests]

[^tool]: Tool
[^registry]: ToolRegistry
[^procedure]: ToolsProcedure
[^paginator]: Paginator
[^tests]: tools/list tests
[^call-tests]: tools/call tests
[^capability-tests]: Capability tool tests
[^task-tests]: Task and async call tests
