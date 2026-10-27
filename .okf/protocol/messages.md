---
type: Protocol
title: Messages
description: Readonly message classes, generate() from a line, toJson() to the wire, fromReceived() from a reply, and the decoder as the one trust boundary.
resource: src/Fabricate/NutsAndBolts/LineDecoder.php
tags: [mcp, json-rpc, decoder]
status: draft
generated: { by: claude-fable/5.1, at: 2026-10-26T15:30:00Z }
sources:
  - id: decoder
    resource: src/Fabricate/NutsAndBolts/LineDecoder.php
    title: LineDecoder
  - id: request
    resource: src/Fabricate/NutsAndBolts/Messages/MCPRequest.php
    title: MCPRequest
---

# One class per wire shape

Every message is a `readonly` class under `NutsAndBolts\Messages`, grouped by feature (`Initialize`, `Ping`, `Tools`, `Resources`, `Prompts`, `Logging`, `Completion`, `Progress`, `Cancellation`, `Errors`, `Responses`).

| Direction | Built by | Sent by |
|---|---|---|
| A request arriving | `Request::generate($id, $method, $params)`: checks the raw decoded values and returns the request or a `JsonRpcError` | — |
| A request leaving | its constructor | `toJson()`; the base on `MCPRequest` leaves `params` out when null and turns an empty array into `{}`; initialize, tools/call and prompts/get override it so nested empty maps are `{}` too |
| A reply arriving | `ReceivedResponse::generate($decoded)`, then `Response::fromReceived($received)` for the typed class, which throws `UnexpectedValueException` on a wrong shape | — |
| A reply leaving | its constructor | `toJson()` |
| A notification | its constructor, or `generate($params)` returning null when malformed | `toJson()` |

# The decoder is the only trust boundary

`LineDecoder::decode($line)` is what a transport calls: it returns a list, one entry per message on the line. A JSON array is a batch (`LineDecoder::isBatch()` says so before decoding); 2025-03-26 has batches and 2025-06-18 removed them, so the transport asks the session (`SessionBag::takesBatches()`) and refuses a batch from a 2025-06-18 session whole. When taken, each element is decoded on its own, so one bad element is one `InvalidRequest` beside its good neighbours; an empty array is one `InvalidRequest` with no id. Replies to a batch leave one per line, never re-batched: a transport writes each response as it is ready, which the spec allows and the async procedures need.

`LineDecoder::decodeString($line)` decodes one object and returns an `MCPMessage` or an `MCPError`. In order: not a JSON object → `ParseError` or `InvalidRequest`; a `method` without `jsonrpc: "2.0"` → `InvalidRequest`; no `id` → a notification, known ones built, unknown ones `UnknownNotification`; a `params._meta.progressToken` that is not a string or int → `InvalidParams`; a known method → its `generate()`; an unknown method → `MethodNotFound` with the id; no method but `id` and `result` or `error` → `ReceivedResponse`.

A progress token is kept beside the request in `RequestMeta` (a WeakMap), not on the request itself.

Known notifications: `initialized`, `cancelled` (client to server); `tools/list_changed`, `resources/list_changed`, `prompts/list_changed`, `resources/updated`, `message`, `progress` (server to client). `progress` carries the 2025-03-26 `message` string when the sender set one.

# 2025-03-26 shapes

| Shape | Where |
|---|---|
| Tool annotations (`title`, `readOnlyHint`, `destructiveHint`, `idempotentHint`, `openWorldHint`) | `ToolAnnotations`, the last argument of `ToolDefinition`; emitted under `annotations` only when given. Hints, never guarantees. |
| Audio content | `Schema\AudioProperty`, beside `TextProperty`, `ImageProperty` and `EmbeddedResourceProperty`; `type: audio`, base64 `data`, an `audio/*` `mimeType` |
| `completions` capability | a key in a server's `$entity_capabilities`, `'completions' => []`, encoded `{}` like every empty capability |
| Protocol version | `supported_mcp_versions` lists `2025-03-26` and `2024-11-05`; a client proposes the greatest, a server echoes a version it speaks or offers its greatest |

# 2025-06-18 shapes

| Shape | Where |
|---|---|
| Protocol version | `supported_mcp_versions` gains `2025-06-18`. The server keeps the negotiated one on `MCPServerBag::$protocol_version`; everything version-dependent reads it. |
| `title` | `ToolDefinition`, `ResourceDefinition`, `ResourceTemplateDefinition`, `PromptDefinition`, `PromptArgument`: display names; `name` stays the identifier. `serverInfo`/`clientInfo` are plain arrays and may carry one. |
| `_meta` | a `meta` array on the four definitions, every content DTO, `ResourceContents` and `CallToolResponse`. `Meta::check()` refuses a key outside the spec's name format when the object is built; `Meta::attach()` adds `_meta` only when non-empty. |
| Content annotations | `Schema\Annotations` (`audience`, `priority` 0..1, `lastModified`) on every content DTO and on resource definitions. Checked at construction. |
| Resource links | `Schema\ResourceLinkProperty`: `type: resource_link` with the resource fields. The resource need not be in `resources/list`. |
| Structured output | `ToolDefinition::$output_schema` (an `ObjectSchema`) goes out as `outputSchema`, as declared and never checked. A tool returns `Schema\StructuredContent($data, $content = null)`; the response carries `structuredContent`, and when `$content` is null one text item with the same JSON, which the spec asks for so older clients see the result. `CallToolResponse::$structured` on both sides. |
| Completion context | `CompleteRequest::$context_arguments` from `params.context.arguments` (an object of strings). A completer is called `($value, $context)`; one written for `($value)` ignores the second argument. |
| Elicitation | `Messages\Elicitation\{ElicitRequest, ElicitResponse, ElicitAnswer, ElicitAction}`; see [elicitation](../mechanism/elicitation.md). |
| Capability gate | `Capabilities::requiredBy($method, $version)` and `declares()`. Server: a method whose capability it did not declare answers `MethodNotFound`. Client: a request for a capability the server did not declare is rejected before it is sent. `completion/complete` needs `completions` only from 2025-03-26, which introduced it. |
| Initialize encoding | client capabilities go out as objects at every depth (`"elicitation": {}`), as server capabilities already did. Go and TypeScript servers refuse an array there. |

# Nothing downstream re-checks

Procedures and nodes receive typed messages and trust their fields. A new message class gets its `generate()` checks at the point the line is read, never later.
