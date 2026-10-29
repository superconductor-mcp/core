---
type: Mechanism
title: Elicitation
description: A handler asks the user mid-call through the client, waits in its fiber, gets accept, decline or cancel. Form mode (2025-06-18) and URL mode (2025-11-25), server side, client side, the schema subset and its checks, timeouts and cancellation.
resource: src/Fabricate/Mechanism/Session/Elicitation.php
tags: [mcp, elicitation, session, client, 2025-06-18, 2025-11-25]
status: draft
generated: { by: claude-opus-5-5/claude-code, at: 2026-10-29T00:00:00Z }
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
  - id: spec-url
    resource: https://modelcontextprotocol.io/specification/2025-11-25/client/elicitation
    title: Elicitation, 2025-11-25 (URL mode)
  - id: modes
    resource: src/Fabricate/NutsAndBolts/Capabilities.php
    title: Capabilities::elicitationModes()
  - id: required
    resource: src/Fabricate/Mechanism/Session/UrlElicitationRequired.php
    title: UrlElicitationRequired
  - id: client-node
    resource: src/Fabricate/Client/Workflows/ClientStartNode.php
    title: ClientStartNode::elicit()
  - id: url-tests
    resource: tests/Mechanism/UrlElicitationTest.php
    title: URL elicitation tests
---

# Server side

`Elicitation` is built per request, like `ProgressReporter`: the session registers one in the container before the procedure runs and puts back `Elicitation::unavailable()` after. A handler takes it in its constructor, so it holds the instance for its own request even when another request's fiber runs between.

`ask(string $message, ObjectSchema $schema): ElicitAnswer`:

1. Refuses (`ElicitationFailed`) when `available()` is false: the session negotiated less than 2025-06-18, the client's initialize did not declare form mode, or no request is in flight. `available()` lets a handler choose a fallback instead of catching.
2. Refuses a schema with a property outside the subset (below) before anything is sent.
3. Sends `elicitation/create` with an id from the session's own ledger, through the same `$send` the request answers on: the pipe on stdio. Parks the id in `SessionBag::$awaiting`.
4. Waits with `Loop::until()` in the request's fiber. The loop keeps turning, so other requests, pings and the answer itself still flow.
5. Ends on the first of: the answer lands in `SessionBag::$answers` (a `ReceivedResponse` the transport handed to `Session::handle()`); the originating request is cancelled; `mcp.elicitation.timeout` seconds pass (default 600). The last two send `notifications/cancelled` for the elicitation id and throw.
6. A JSON-RPC error answer throws. An `accept` whose content breaks the schema throws. `decline` and `cancel` are answers, returned as they are.

Only a procedure that runs in a fiber can wait: `tools/call`, `resources/read`, `prompts/get`, `completion/complete` (`Procedure::ASYNC`). A throw inside a tool becomes an `isError` result like any other failure.

# Modes

A client declares the modes it takes under `elicitation`. `Capabilities::elicitationModes()` reads them: none without the capability; form only before 2025-11-25; from 2025-11-25 whichever of `form` and `url` it names, an empty object still meaning form. `available($mode)` checks one, and the server never sends a mode the client did not declare.[^modes][^url-tests]

# URL mode (2025-11-25)

For what must not pass through the client (credentials, payment, a third-party sign-in), a handler sends the user to a URL instead of a form.[^spec-url]

`askUrl(string $message, string $url, string $elicitation_id): ElicitAnswer` sends `elicitation/create` with `mode: "url"`, the `elicitationId` and the `url` (checked as a URL), and waits as `ask()` does. `accept` means the user agreed to open the URL, not that they finished, so it carries no content; any content a client sends with it is dropped.[^service]

When the out-of-band interaction is done, the server queues `ElicitationCompleteNotification($elicitation_id)` through `notify()`. It goes out with the session's next flush, and `MCPServerBag::wants()` passes it only to a session whose client declared URL mode.[^url-tests]

A handler that can't go on until the user finishes one or more URL interactions throws `UrlElicitationRequired` with each `{message, url, elicitationId}`. `tools/call`, `resources/read` and `prompts/get` answer it with error `-32042` (`UrlElicitationRequiredError`) listing them as URL elicitations, which the client may retry after their completions. A client without URL mode gets `InternalError` instead, since the spec allows `-32042` only where URL elicitation is possible.[^required][^url-tests]

The spec makes the server verify that whoever opens the URL is the user who started the elicitation, and forbids URLs that carry credentials or are pre-authenticated. Those are the handler's to keep; nothing here can check them.[^spec-url]

# Client side

`MCPClient::onElicit(Closure)` sets the form handler and `onElicitUrl(Closure)` the URL one; each must be set before `open()`, since initialize declares them. Form alone declares `elicitation: {}`, which every version reads as form; with URL the client names each mode it has (`{"url":{}}` or `{"form":{},"url":{}}`). `onElicitationComplete(Closure)` is told the `elicitationId` of each `notifications/elicitation/complete`. `ClientManager` has the same three for every client it builds afterwards. A handler gets `(ElicitRequest $request, MCPClient $client)` and returns an `ElicitAnswer` (`accept($content)`, `decline()`, `cancel()`); `$request->mode` is `form` or `url`.[^client-node]

A URL handler must show the full URL and its host, and open it outside the client only after the user agrees: never fetch it first or open it unasked. Its `accept` goes back without content.[^spec-url]

`ClientStartNode` checks a form `accept` against `requested_schema` before it goes back; a mismatch or a throw from the handler is answered with `InternalError`. A client with no elicitation handler answers `MethodNotFound`, and never declared the capability; one with a handler for the other mode only answers `InvalidParams`.[^client-node]

The decoder refuses an `elicitation/create` whose schema leaves the subset, a URL request without a valid `url` and `elicitationId`, or an unknown `mode` (`InvalidParams`), so a handler only ever sees a request it can show.

# The schema subset

The form is an `ObjectSchema` of schema primitives. 2025-06-18 allowed the first five rows; 2025-11-25 (SEP-1330, SEP-1034) adds `default` on every one, the titled single-select and both multi-selects. Only these are allowed, each with these keywords:

| Primitive | Keywords |
|---|---|
| `StringProperty(description, title, min_length, max_length, format, default)` | `format`: `email`, `uri`, `date`, `date-time` |
| `EnumProperty(values, names, description, title, titled, default)`, untitled single-select | `enum` |
| the same with `names`, legacy | `enum` and `enumNames` (one per value; deprecated by the spec, still read and written) |
| the same with `titled: EnumProperty::ONE_OF`, titled single-select | `oneOf`: a list of `{const, title}`; it excludes `enum` and `enumNames` |
| `ArrayProperty(items, description, title, min_items, max_items, default)` over an untitled `EnumProperty`, untitled multi-select | `type: array`, `items: {type: string, enum}`, `minItems`, `maxItems` |
| the same over `EnumProperty(..., titled: EnumProperty::ANY_OF)`, titled multi-select | `items: {anyOf: [{const, title}]}`, no `type` on the items |
| `NumberProperty` / `IntegerProperty(description, title, minimum, maximum, default)` | |
| `BooleanProperty(description, title, default)` | |

A `default` is checked against the field's own constraints when the primitive is built or the schema is read (length, format, bounds, membership of the values, item count), so a form never offers a prefill its own answer check would refuse. Any other array, or items with another keyword, is outside the subset.

`ObjectSchema::isFlat()` is true when every property is one of those. `ObjectSchema::fromArray()` reads a received schema into primitives and refuses any other type or keyword. `problem($content)` names the first value that breaks the schema, including keys it does not define; each primitive checks its own value; a multi-select answer is a list of strings, each of which must be one of the field's values, and the list must hold `minItems` to `maxItems` of them. Both sides call it, so the server and client agree on what "matches" means. `date` is `YYYY-MM-DD` and must be a real date; `date-time` is RFC 3339.

The spec forbids asking for sensitive information this way. Nothing can check that; it is the handler author's rule.

[^service]: Elicitation
[^spec-url]: Elicitation, 2025-11-25 (URL mode)
[^modes]: Capabilities::elicitationModes()
[^required]: UrlElicitationRequired
[^client-node]: ClientStartNode::elicit()
[^url-tests]: URL elicitation tests
