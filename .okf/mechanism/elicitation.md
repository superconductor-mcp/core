---
type: Mechanism
title: Elicitation
description: 2025-06-18. A handler asks the user mid-call through the client, waits in its fiber, gets accept, decline or cancel. Server side, client side, the schema subset and its checks, timeouts and cancellation.
resource: src/Fabricate/Mechanism/Session/Elicitation.php
tags: [mcp, elicitation, session, client, 2025-06-18]
status: draft
generated: { by: claude-sonnet-5-5/claude-code, at: 2026-10-28T23:00:00Z }
sources:
  - id: service
    resource: src/Fabricate/Mechanism/Session/Elicitation.php
    title: Elicitation
  - id: schema
    resource: src/Fabricate/NutsAndBolts/Schema/ObjectSchema.php
    title: ObjectSchema
  - id: messages
    resource: src/Fabricate/NutsAndBolts/Messages/Elicitation
    title: ElicitRequest, ElicitResponse, ElicitAnswer, ElicitAction
  - id: spec
    resource: https://modelcontextprotocol.io/specification/2025-06-18/client/elicitation
    title: Elicitation, 2025-06-18
---

# Server side

`Elicitation` is built per request, like `ProgressReporter`: the session registers one in the container before the procedure runs and puts back `Elicitation::unavailable()` after. A handler takes it in its constructor, so it holds the instance for its own request even when another request's fiber runs between.

`ask(string $message, ObjectSchema $schema): ElicitAnswer`:

1. Refuses (`ElicitationFailed`) when `available()` is false: the session negotiated less than 2025-06-18, the client's initialize did not declare `elicitation`, or no request is in flight. `available()` lets a handler choose a fallback instead of catching.
2. Refuses a schema with a property outside the subset (below) before anything is sent.
3. Sends `elicitation/create` with an id from the session's own ledger, through the same `$send` the request answers on: the pipe on stdio. Parks the id in `SessionBag::$awaiting`.
4. Waits with `Loop::until()` in the request's fiber. The loop keeps turning, so other requests, pings and the answer itself still flow.
5. Ends on the first of: the answer lands in `SessionBag::$answers` (a `ReceivedResponse` the transport handed to `Session::handle()`); the originating request is cancelled; `mcp.elicitation.timeout` seconds pass (default 600). The last two send `notifications/cancelled` for the elicitation id and throw.
6. A JSON-RPC error answer throws. An `accept` whose content breaks the schema throws. `decline` and `cancel` are answers, returned as they are.

Only a procedure that runs in a fiber can wait: `tools/call`, `resources/read`, `prompts/get`, `completion/complete` (`Procedure::ASYNC`). A throw inside a tool becomes an `isError` result like any other failure.

# Client side

`MCPClient::onElicit(Closure)` sets the handler and adds `elicitation: {}` to the client's capabilities, so it must be set before `open()`. `ClientManager::onElicit()` sets one handler for every client the manager builds afterwards. The handler gets `(ElicitRequest $request, MCPClient $client)` and returns an `ElicitAnswer` (`accept($content)`, `decline()`, `cancel()`).

`ClientStartNode` checks an `accept` against `requested_schema` before it goes back; a mismatch or a throw from the handler is answered with `InternalError`. A client with no handler answers `MethodNotFound`, and never declared the capability.

The decoder refuses an `elicitation/create` whose schema leaves the subset (`InvalidParams`), so a handler only ever sees a schema it can render.

# The schema subset

The form is an `ObjectSchema` of schema primitives. 2025-06-18 allows only these, each with these keywords:

| Primitive | Keywords |
|---|---|
| `StringProperty(description, title, min_length, max_length, format)` | `format`: `email`, `uri`, `date`, `date-time` |
| `EnumProperty(values, names, description, title)` | `enum`, `enumNames` (one per value) |
| `NumberProperty` / `IntegerProperty(description, title, minimum, maximum)` | |
| `BooleanProperty(description, title, default)` | |

`ObjectSchema::isFlat()` is true when every property is one of those. `ObjectSchema::fromArray()` reads a received schema into primitives and refuses any other type or keyword. `problem($content)` names the first value that breaks the schema, including keys it does not define; each primitive checks its own value. Both sides call it, so the server and client agree on what "matches" means. `date` is `YYYY-MM-DD` and must be a real date; `date-time` is RFC 3339.

The spec forbids asking for sensitive information this way. Nothing can check that; it is the handler author's rule.
