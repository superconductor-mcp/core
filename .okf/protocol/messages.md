---
type: Protocol
title: Messages
description: Readonly message classes, generate() from a line, toJson() to the wire, fromReceived() from a reply, and the decoder as the one trust boundary.
resource: src/Fabricate/NutsAndBolts/LineDecoder.php
tags: [mcp, json-rpc, decoder]
status: draft
generated: { by: claude-fable/5.1, at: 2026-10-26T04:30:00Z }
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

`LineDecoder::decodeString($line)` returns an `MCPMessage` or an `MCPError`. In order: not a JSON object → `ParseError` or `InvalidRequest`; a `method` without `jsonrpc: "2.0"` → `InvalidRequest`; no `id` → a notification, known ones built, unknown ones `UnknownNotification`; a `params._meta.progressToken` that is not a string or int → `InvalidParams`; a known method → its `generate()`; an unknown method → `MethodNotFound` with the id; no method but `id` and `result` or `error` → `ReceivedResponse`.

A progress token is kept beside the request in `RequestMeta` (a WeakMap), not on the request itself.

Known notifications: `initialized`, `cancelled` (client to server); `tools/list_changed`, `resources/list_changed`, `prompts/list_changed`, `resources/updated`, `message`, `progress` (server to client).

# Nothing downstream re-checks

Procedures and nodes receive typed messages and trust their fields. A new message class gets its `generate()` checks at the point the line is read, never later.
