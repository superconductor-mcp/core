---
type: Module
title: Message decoding
description: How an inbound JSON-RPC payload becomes a schema class of whichever protocol version it fits, or an MCPError naming the problem.
resource: src/Fabricate/Mechanism/MessageHydrator.php
tags: [decoding, hydration, versions, json-rpc]
status: draft
generated: { by: claude-code/claude-fable-5-1, at: 2026-11-02T00:00:00Z }
sources:
  - id: decoder
    resource: src/Fabricate/Mechanism/MessageDecoder.php
    title: MessageDecoder
  - id: hydrator
    resource: src/Fabricate/Mechanism/MessageHydrator.php
    title: MessageHydrator
  - id: versions
    resource: src/Fabricate/Mechanism/Hydration
    title: Per-version hydrators, Wire reader and failure types
  - id: tests
    resource: tests/Feature/Decoding/MessageHydratorTest.php
    title: MessageHydrator tests
  - id: python
    resource: https://github.com/modelcontextprotocol/python-sdk/blob/main/src/mcp-types/mcp_types/methods.py
    title: Python SDK per-version method tables
  - id: go
    resource: https://github.com/modelcontextprotocol/go-sdk/blob/main/internal/jsonrpc2/messages.go
    title: Go SDK two-stage decode
---

Decoding has two stages, following the Go SDK's split between envelope and content.[^go]

# Stage 1: envelope

`MessageDecoder::fromWire(string)` and `::fromBody(array)` read the JSON-RPC envelope without any protocol version and classify the message as a `DecodedRequest`, `DecodedNotification`, `DecodedResponse`, `DecodedError` or `InvalidMessage`, or a `DecodedBatch` of those. An `InvalidMessage` carries the RPC code and the recovered id, ready to answer with.[^decoder] Transports call this where bytes arrive: the stdio pipe per line, web middleware per body.

# Stage 2: schema class

`MessageHydrator::hydrate(DecodedMessage, ?string $request_method)` returns a `Request`, `Notification`, `Result` or `MCPError` from the schema tree.[^hydrator] It takes no protocol version: each version has a hand-written `VersionHydrator` that builds its own classes field by field through `Wire`, a typed reader that throws `HydrationException` naming the member and what it must be.[^versions]

The hydrators run newest first. Three rules decide the outcome:

| Situation | Outcome |
|---|---|
| A version builds the message | That class is returned, so a message that fits several versions takes its newest shape. |
| A version can tell the message is another version's (`initialize` naming a different `protocolVersion`; 2026-07-28 params without `_meta.io.modelcontextprotocol/protocolVersion`; a 2026-07-28 result without `resultType`) | It throws `NotThisVersion`, which never outranks a real failure. |
| No version builds it | The highest-ranked failure becomes the error: a shape failure (-32602) from a version that knows the method, else unknown method (-32601), else the last refusal. |

Per-version tables keyed by method, with absence as the version gate, come from the Python SDK.[^python]

Responses carry no method, so `$request_method` must come from the caller's record of what it sent; without it the hydrator answers -32600. A 2025-11-25 response carrying `task` for `tools/call`, `sampling/createMessage` or `elicitation/create` builds `CreateTaskResult`. A 2026-07-28 result with `resultType: input_required` builds `InputRequiredResult`, whose `inputRequests` and the matching `inputResponses` on a retried request are keyed objects, not lists.

The flat-era classes (2024-11-05 to 2025-06-18) come back inside their `JSONRPCRequest` or `JSONRPCNotification` envelope so the id survives; `ProcedureRouter::dispatch` unwraps the envelope.

# Negotiated version

Once a session has negotiated a version, `MessageHydrator::hydrateAs($message, $protocol_version, $request_method)` builds the message with that version's hydrator only. A ping that `hydrate()` returns as the 2025-11-25 `PingRequest` comes back as the 2024-11-05 one in a 2024-11-05 session, so the procedure router accepts it. A message that version doesn't have, or a version string that isn't one, comes back as an `MCPError`.

# Verification

54 Pest cases cover every message kind, the version choice, task and input-required results, and nine failure shapes.[^tests] A scratch round trip of the 221 message fixtures used for `schema.json` conformance passes 219; the two differences are a 2025-06-18 `elicitation/create` that also fits 2025-11-25, whose class re-emits `mode: form`.

[^decoder]: `src/Fabricate/Mechanism/MessageDecoder.php`
[^hydrator]: `src/Fabricate/Mechanism/MessageHydrator.php`
[^versions]: `src/Fabricate/Mechanism/Hydration/`
[^tests]: `tests/Feature/Decoding/MessageHydratorTest.php`
[^python]: Python SDK `methods.py`
[^go]: Go SDK `messages.go`
