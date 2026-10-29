---
type: Server
title: Definitions
description: Tools, resources, templates, prompts, completers: the DTOs, the attributes, what handle() gets and returns.
resource: src/Fabricate/NutsAndBolts
tags: [mcp, server, tools, resources, prompts]
status: draft
generated: { by: claude-sonnet-5-5/claude-code, at: 2026-10-29T00:00:00Z }
sources:
  - id: tool
    resource: src/Fabricate/NutsAndBolts/ToolDefinition.php
    title: ToolDefinition
  - id: calltool
    resource: src/Fabricate/Mechanism/Rpc/Procedures/Tools/CallToolProcedure.php
    title: CallToolProcedure
  - id: make-command
    resource: src/Fabricate/Core/Commands/MakeServerCommand.php
    title: MakeServerCommand
  - id: make-stub
    resource: stubs/mcp-server.stub
    title: The server stub
  - id: make-tests
    resource: tests/Core/MakeServerCommandTest.php
    title: make:mcp-server generates a server discovery finds
  - id: icon
    resource: src/Fabricate/NutsAndBolts/Icon.php
    title: Icon
  - id: icon-tests
    resource: tests/NutsAndBolts/Shapes20251125Test.php
    title: Icons are sent as declared and checked when built
---

# A definition names an action

Each DTO's `action` is a `Closure` or a class name. A class is built through the container (so it may take the loop, its server or a `ProgressReporter` in its constructor) only when it carries the matching attribute and has a public `handle()`; otherwise the procedure answers `MethodNotFound` (tools) or `InternalError` (resources, prompts).

| Definition | Attribute | `handle()` arguments | returns |
|---|---|---|---|
| `ToolDefinition(name, description, properties, required, action)` | `#[ToolCall]` | the call's arguments by name; `required` checked, unknown names refused, PHP's own required parameters checked, all as `InvalidParams`, or from 2025-11-25 as a `CallToolResponse` with `isError` and the problem as text | one `SchemaPrimitive` or a list; anything else or a throw → `isError` result |
| `ResourceDefinition(uri, name, description, mime_type, action)` | `#[ResourceRead]` | none | `ResourceContents` |
| `ResourceTemplateDefinition(uri_template, name, description, mime_type, action, completers)` | `#[ResourceRead]` | the template's `{variables}`, matched per path segment and url-decoded | `ResourceContents` |
| `PromptDefinition(name, description, arguments, action)`, `PromptArgument(name, description, required, completer)` | `#[PromptGet]` | the prompt's arguments by name, always strings | a list of `PromptMessage` |
| a `completer` | `#[Completes]` | `handle(string $value)` | `CompletionResult` |

# Icons

2025-11-25 (SEP-973). Each of the four definitions takes `icons`, a list of `NutsAndBolts\Icon` (`src`, `mime_type`, `sizes`, `theme`) or of spec-shaped arrays, which `Icon::list()` reads when the definition is built; an empty `src`, a `theme` other than `light` or `dark`, or a `sizes` that is not a list of strings throws `InvalidArgumentException` naming the icon. They go out under `icons` exactly as declared, ungated by version like `title`, and the key is left out with none.[^icon-tests] A server's identity (`$entity_info`, read through `entityInfo()`) takes the same `icons`, checked the same way, plus string `description` and `websiteUrl`.

# Tool names

2025-11-25 (SEP-986). `ToolDefinition` throws `InvalidArgumentException` unless the name matches `/\A[A-Za-z0-9_.-]{1,128}\z/`; the message says the allowed characters and length. It is checked when the definition is built, whatever version a session negotiates, so a server cannot list a name a 2025-11-25 client would reject.[^icon-tests]

# Bad arguments

2025-11-25 (SEP-1303). `CallToolProcedure` checks the call's arguments before running the action: a missing required argument, an unknown one, a PHP parameter left unfilled, or a value that does not fit its declared property (`SchemaProperty::problem()`: type, length, range, format, enum; `text: 5` is `Invalid argument text: must be a string.` rather than coerced to `"5"`). On a session that negotiated 2025-11-25 or later the problem comes back as a `CallToolResponse` with `isError: true` and the message as one text item, so the model can read it and call again; on an older session it stays `InvalidParams`. An unknown or unavailable tool is a protocol error (`ToolCallError`) at every version, because there is nothing to correct and call again.[^icon-tests]

# Schema primitives

A schema is built from primitives, each a `SchemaProperty` that renders itself and checks a value (`problem()`): `StringProperty`, `NumberProperty`, `IntegerProperty`, `BooleanProperty`, `EnumProperty`, `ArrayProperty(items)` and `ObjectSchema(properties, required)`, which nests. A tool's `properties` are primitives by name; its `output_schema` is an `ObjectSchema`. Results are `TextProperty(text)`, `ImageProperty(data, mime_type)` and `EmbeddedResourceProperty(uri, mime_type, text|blob)`, which wraps `ResourceContents`. Every one is `Arrayable` and renders its own wire shape.

# Lists are computed per call

`tools()`, `resources()`, `resourceTemplates()` and `prompts()` return the `$tools` etc. arrays by default; override them to decide per call. Each list request pages its result through `Paginator` with an opaque base64 offset cursor and `mcp.pagination.page_size`; a cursor the paginator did not make is `InvalidParams`.

# Generating a server

`php computer make:mcp-server <Name>` is a framework `GeneratorCommand`: it writes `<Name>.php` under `mcp.servers.default_discovery_namespace` (`App\Mcp\Servers` by default, so `app/Mcp/Servers/`), refuses to overwrite one that exists unless `--force`, and names the server the kebab-cased class name, as `ServerRepository::nameFor()` does. The stub declares `#[Server]`, extends `MCPServer`, sets `entity_info` and a `tools` capability, and offers one `echo` tool whose action is a closure. `stubs/mcp-server.stub` in the application overrides the package's. The core provider binds the command in the container, as Computer resolves commands through it. The test generates into a temporary application and checks that `DiscoverServers` finds the class and the tool runs.

# Progress and cancellation

The transport registers a `ProgressReporter` for the request in the container before the action is built. `report($progress, $total)` sends `notifications/progress` for a request that carried a `progressToken`, only when the value increased, at most once per `mcp.progress.min_interval` except the report that reaches the total; `finish()` after the response stops it. A `notifications/cancelled` for a request still in flight suppresses its response.
[^icon-tests]: Icons are sent as declared and checked when built
