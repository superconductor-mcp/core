---
type: Module
title: Client capabilities
description: How an MCPClient answers roots/list, sampling/createMessage and elicitation/create from the developer's concretes, runs them as 2025-11-25 tasks, announces roots changes, and fulfils 2026-07-28 input requests.
resource: src/Fabricate/Core/Rpc/Procedures/ClientCapabilityProcedure.php
tags: [client, roots, sampling, elicitation, tasks, mrtr]
status: draft
generated: { by: claude-code/claude-opus-5-5, at: 2026-11-03T22:00:00Z }
sources:
  - id: procedure
    resource: src/Fabricate/Core/Rpc/Procedures/ClientCapabilityProcedure.php
    title: ClientCapabilityProcedure
  - id: client
    resource: src/Fabricate/Client/MCPClient.php
    title: MCPClient
  - id: configuration
    resource: src/Fabricate/Client/ClientConfiguration.php
    title: ClientConfiguration
  - id: tests
    resource: tests/Feature/Client/ClientCapabilitiesTest.php
    title: Client capability tests
  - id: elicitation-spec
    resource: https://modelcontextprotocol.io/specification/2025-11-25/client/elicitation
    title: MCP 2025-11-25 elicitation
  - id: sampling-spec
    resource: https://modelcontextprotocol.io/specification/2025-11-25/client/sampling
    title: MCP 2025-11-25 sampling
  - id: mrtr-spec
    resource: https://modelcontextprotocol.io/specification/2026-07-28/basic/patterns/mrtr
    title: MCP 2026-07-28 multi round-trip requests
---

# The developer's concretes

The package never samples a model, asks a user or picks roots: the developer writes a concrete for each and the client configuration names it. `roots()`, `sampling()` and `elicitation()` return a class extending `Roots`, `Sampling` or `Elicitation` (in `Fabricate\Client`), or null. The capability itself is declared as on a server, with `$roots`, `$sampling`, `$elicitation` and `$tasks`.[^configuration]

Each concrete implements `handle()`, called through the container with the request (by its class, or as `$request`), the client configuration, `$protocol_version` and, when asked for, the `RequestContext`. It returns the result class of the request's version: `ListRootsResult`, `CreateMessageResult` or `ElicitResult`. With `protected bool $async = true` it may return nothing and hand the result to `finish()` later, or a failure to `fail()`. A refusal is a thrown `SuperconductorMCPException` with a code the version knows; a user who rejects sampling is `SamplingErrorCode::USER_REJECTED` (-1), which the sampling pages of 2024-11-05 through 2025-11-25 give and every one of those versions' errors now accepts.[^procedure][^sampling-spec]

The devbox app's `CuriousClient` names `CuriousRoots` (`App\Mcp\Roots`), `CuriousSampling` (`App\Mcp\Sampling`) and `CuriousElicitation` (`App\Mcp\Elicitation`).

# Answering a server

`RootsProcedure`, `SamplingProcedure` and `ElicitationProcedure` route through `ClientCapabilityProcedure::answer()` and return the result, ready for `MCPClient` to send. Before the concrete runs:[^procedure][^tests]

| Request | Refusal |
|---|---|
| A capability the client didn't declare, or a version without the method | -32601 (the router) |
| `elicitation/create` in a mode the client didn't declare (only `form` on 2025-06-18; an empty declaration means `form`) | -32602, as the elicitation page requires |
| `sampling/createMessage` with `tools` from a client without `sampling.tools` | -32602 |

A client that declares `roots` but names no concrete exposes no roots: `roots/list`, and a 2026-07-28 `roots/list` input request, get an empty `roots` list. A concrete that answers in another version is the developer's mistake: it is reported, and the server gets only `Internal error`.[^procedure]

A concrete that runs async answers later through `RequestContext::reply()`, as mail. On 2025-11-25 a request carrying `task` runs as a task when the client offers `tasks.requests.sampling.createMessage` or `tasks.requests.elicitation.create`: the answer is `CreateTaskResult`, the outcome settles the task in `ClientConfiguration::taskStore()`, and `notifications/tasks/status` follows. The client serves `tasks/get`, `tasks/result`, `tasks/list` and `tasks/cancel` from that store, through the same `TasksProcedure` as a server. A task request for a method the client doesn't offer tasks for is answered in line.[^procedure][^tests]

# Roots changes

`Roots::changed()` fires `RootsChanged`. Every open `MCPClient` that declared `roots.listChanged` queues `notifications/roots/list_changed`; 2026-07-28 has no such notification. `close()` takes a client off the dispatcher's list.[^client]

# 2026-07-28 input requests

When a `tools/call`, `prompts/get` or `resources/read` the client sent is answered with `InputRequiredResult`, the client fulfils every `inputRequests` entry from the same concretes, with the same refusals, then sends the request again through `retry()`: a new id, the answers as `inputResponses`, and `requestState` exactly as the server sent it. The closure given with the original request, or `ResponseReceived`, gets the retry's final answer. An input the client can't give ends the request with its error, and nothing is retried.[^client][^mrtr-spec][^tests]

[^procedure]: ClientCapabilityProcedure
[^client]: MCPClient
[^configuration]: ClientConfiguration
[^tests]: Client capability tests
[^elicitation-spec]: MCP 2025-11-25 elicitation
[^sampling-spec]: MCP 2025-11-25 sampling
[^mrtr-spec]: MCP 2026-07-28 multi round-trip requests

# Spec checks the client makes

- **Roots:** a root is exposed only if its `uri` is a `file://` URI with an absolute path and no `.` or `..` segment, after percent-decoding. The roots page requires `file://` URIs and requires clients to validate root URIs against path traversal. A root that fails is left out of the answer and reported.[^procedure]
- **Sampling with tools (2025-11-25, 2026-07-28):** a user message holding tool results may hold nothing else, and every assistant tool use must be answered by a tool result in the user message right after it. A request breaking either gets -32602, the code the sampling page gives for both.[^procedure]
- **`notifications/elicitation/complete` (2025-11-25):** passed on as `NotificationReceived` only for a URL elicitation still open with the client, and only once; any other is ignored, as the elicitation page requires. A URL elicitation opens when the server sends `elicitation/create` in URL mode, or names it in a `URLElicitationRequiredError` (-32042).[^client]

