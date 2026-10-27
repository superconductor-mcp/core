# Log

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
