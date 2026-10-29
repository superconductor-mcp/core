# Log

## 2026-10-29 (2025-11-25)

* **Argument types (SEP-1303)**: `tools/call` checks each argument with a declared property against it (`SchemaProperty::problem()`), so a wrong type, length, range, format or enum value is a bad argument like a missing one, instead of PHP coercing it ([definitions](server/definitions.md)).

## 2026-10-28 (2025-11-25)

* **URL elicitation (SEP-1036)**: `Elicitation::askUrl()`, `ElicitationCompleteNotification` (sent only to a session whose client declared URL mode), `UrlElicitationRequired` answered as `-32042` from `tools/call`, `resources/read` and `prompts/get`; `Capabilities::elicitationModes()` reads `form`/`url`, an empty object still meaning form; the client's `onElicitUrl()` and `onElicitationComplete()` ([elicitation](mechanism/elicitation.md), [errors](protocol/errors.md)).
* **Protocol 2025-11-25 added**, the three earlier versions kept: `supported_mcp_versions` lists it first, so negotiation picks it and `MCPClient` proposes it ([messages](protocol/messages.md), [config](reference/config.md)).
* **Icons (SEP-973)**: `NutsAndBolts\Icon` and an `icons` argument on `ToolDefinition`, `ResourceDefinition`, `ResourceTemplateDefinition` and `PromptDefinition`, and in a server's `$entity_info` beside `description` and `websiteUrl`. Sent as declared, checked when built ([definitions](server/definitions.md)).
* **Tool names (SEP-986)**: `ToolDefinition` throws unless the name is 1 to 128 characters of `A-Z a-z 0-9 _ - .`, at every protocol version ([definitions](server/definitions.md)).
* **Elicitation forms (SEP-1330, SEP-1034)**: `default` on string, number, integer, boolean and every enum form, checked against the field's own constraints; titled single-select (`oneOf`); untitled and titled multi-select (`ArrayProperty` with `min_items`, `max_items`, `default`); legacy `enumNames` kept. An answer's content may hold a list of strings, and the answer check verifies each value and the item count ([elicitation](mechanism/elicitation.md)).
* **Bad tool arguments (SEP-1303)**: from 2025-11-25 a missing or unknown argument is a `CallToolResponse` with `isError` and the problem as text; older sessions keep `InvalidParams`, and an unknown tool stays a protocol error ([definitions](server/definitions.md)).

## 2026-10-28 (0.11.0)

* **Package renamed** `superconductor/mcp` to `superconductor/core`; the transport packages are `superconductor/stdio` and, planned, `superconductor/web` (built on venusian/serve). The 0.10 `semiconductor/*` names are retired.
* **Entities**: `MCPMechanism` and `Contracts\Mechanism\Mechanism` became `Mechanism\MCPEntity` and `Contracts\Mechanism\MCPEntity` ([roles](architecture/roles.md)).
* **Contracts**: `Contracts\Server\MCPServer` and `Contracts\Client\MCPClient` added. Code outside the two classes, and the container id the stdio sketch registers, name the contracts; `MCPServer` is abstract and implements its contract, `MCPClient` implements its own ([roles](architecture/roles.md)).
* **Providers flattened**: `Server\ServerServiceProvider` and `Client\ClientServiceProvider` replace the `Providers/` folders ([transports](architecture/transports.md)).
* **Versions**: root and split manifests `^0.11.0` / `0.11.x-dev`, `MCPClient::VERSION` `0.11.0`, PHP `~8.4.27||^8.5.12`; `venusian/framework ^0.11.0` joins require-dev because the code calls `config()` and `app()`. No wire or behaviour change; the protocol line is unchanged (2025-06-18, 2025-03-26, 2024-11-05).
* **Contracts name only Contracts and Voyager types**: `ClientStatus` moved to `Contracts\Client\ClientStatus` and `LogLevel` to `Contracts\NutsAndBolts\LogLevel`. `Contracts\Client\MCPClient` lost `receive()`, `connectionClosed()`, `fill()`, `filled()`, `handshakeDone()` and `fail()` (the start node, the test connection and stdio's `StdioConnection` hold the concrete client) and gained `listResources()`, `listResourceTemplates()`, `readResource()`, `getPrompt()`, `serverCapabilities()`, `protocolVersion()` and `resourceTemplates()`. The `Contracts` split requires `venusian-voyager/contracts ^0.11.0`. `Contracts\NutsAndBolts\MCPNotification` (an `RPCMessage` with a `method` property and `toJson()`) is new, and `MCPServerBag::wants()` and the session flush take it, so a contract-only notification is sent; `NutsAndBolts\Messages\MCPNotification` implements it, and `Contracts\Server\MCPServer::notify()` and the server's `notify()` and `advertises()` take it, so no `Contracts` file imports a `Fabricate\NutsAndBolts`, `Client`, `Server` or `Mechanism` class ([roles](architecture/roles.md)).
* **`make:mcp-server`**: writes a server stub under the discovery namespace; `stubs/mcp-server.stub`, `MakeServerCommand`, bound by the core provider ([definitions](server/definitions.md), [config](reference/config.md)).
* **Removed** the empty `Core\Commands\MakeClientCommand`: clients are spawned by `ClientManager` from `mcp_servers`, so no `make:mcp-client` is planned.
* **Removed** `client.stream_reconnects` from `config/mcp.php` ([config](reference/config.md)): it belonged to an HTTP client that is not planned; superconductor/web serves, it does not consume.
* Pest suite: 68 tests, 327 assertions.

## 2026-10-27 (2025-06-18)

* **Protocol 2025-06-18 added**, both earlier versions kept. The server records the negotiated version on its bag ([messages](protocol/messages.md)).
* **Batches by version**: `LineDecoder::isBatch()`, `SessionBag::takesBatches()`; a 2025-06-18 session's batch is refused whole ([session](mechanism/session.md)).
* **Capability gate** on both sides: `Capabilities` ([session](mechanism/session.md), [client](client/client.md)).
* **Elicitation**, server and client, with a complete check of the 2025-06-18 schema subset ([elicitation](mechanism/elicitation.md)).
* **Structured output**, **resource links**, **content annotations**, **`title`**, **`_meta`**, **completion context** ([messages](protocol/messages.md)).
* **Initialize capabilities encoded as objects at every depth**: `"elicitation": []` was refused by a Go server.
* Pest suite: 62 tests, 302 assertions. Proven with the TypeScript SDK 1.22.0 client (2025-06-18, elicitation handler) over stdio and Streamable HTTP, and against neo4j-mcp 1.5.3.
* **Schemas are primitives**: `SchemaProperty` contract; `NumberProperty`, `IntegerProperty`, `BooleanProperty`, `EnumProperty`, `ArrayProperty`, `ObjectSchema` beside `StringProperty`. Tool inputs, output schemas and elicitation forms use them; the array checker is gone ([definitions](server/definitions.md)).
* **Client content is typed**: `CallToolResponse`, `ReadResourceResponse`, `GetPromptResponse` hold only primitives; `fromReceived()` reads wire content through `Schema\Content` and `PromptMessage::fromArray()` ([client](client/client.md)).

## 2026-10-26 (2025-03-26)

* **`Mechanism\Session`**: the server-side session logic lifted out of semiconductor/stdio so the HTTP transport runs the same node ([session](mechanism/session.md)). `SessionBag`, the three lifecycle states and the two notification actions moved here; stdio's bag and node now extend and delegate.
* **Protocol 2025-03-26 added**, 2024-11-05 kept: `supported_mcp_versions` lists both ([messages](protocol/messages.md)).
* **JSON-RPC batches received**: `LineDecoder::decode()` returns a list per line; every transport calls it. Batches are never sent.
* **Tool annotations** (`ToolAnnotations`), **audio content** (`AudioProperty`), **progress `message`**, **`completions` capability** ([messages](protocol/messages.md)).
* **Ready guarded by the client's own flag**, not the promise's lagging one ([client](client/client.md)).
* `venusian-voyager/config` in require-dev: the suites build a config repository by hand.
* Pest suite: 44 tests, 182 assertions. Proven against the TypeScript SDK's `StreamableHTTPServerTransport` and a production Streamable HTTP server through semiconductor/sse.

## 2026-10-26

* **Client over stdio**: `ClientManager`, `MCPClient`, `ClientBag`, `ClientStartNode`, the `Connection` contract; requests gained `toJson()`, responses `fromReceived()`, the decoder the server-to-client notifications ([client](client/client.md), [messages](protocol/messages.md)). Proven against the dev app's concept server and neo4j-mcp 1.5.3.
* **Two id ledgers per side** replace the server's shared ledger; server pings back on ([ids](protocol/ids.md)).
* **Decoder errors keep their id and code** on the way out; unknown notifications are never answered; every method message must carry `jsonrpc: "2.0"` ([errors](protocol/errors.md), [messages](protocol/messages.md)).
* **`Procedure::ASYNC`**: tool calls, resource reads, prompt gets and completion run in a loop fiber; cancellation and pings work mid-flight ([procedures](mechanism/procedures.md)).
* **Caches hold every page**; the handshake moved out of promise callbacks into the node ([client](client/client.md)).
* Pest suite: 39 tests, 159 assertions. Split manifests filled; README, SECURITY, AGENTS, CLAUDE, CI added.

## 2026-10-25

* Server side of 2024-11-05 complete over stdio: lifecycle, tools, resources, templates, subscriptions, prompts, completion, logging, pagination, progress, cancellation, list_changed, server pings.
