---
type: Concept
title: Stateless servers (2026-07-28)
description: How an MCPServer answers 2026-07-28 requests with no handshake - per-request _meta, server/discover, serverInfo, version errors, logging, progress, multi round-trip requests and subscriptions/listen.
resource: src/Fabricate/Server/MCPServer.php
tags: [stateless, 2026-07-28, discover, mrtr, subscriptions, logging]
status: draft
generated: { by: claude-code/claude-opus-5-5, at: 2026-11-03T00:00:00Z }
sources:
  - id: entity
    resource: src/Fabricate/Mechanism/Entity.php
    title: Entity
  - id: server
    resource: src/Fabricate/Server/MCPServer.php
    title: MCPServer
  - id: procedure
    resource: src/Fabricate/Core/Rpc/Procedures/Procedure.php
    title: Procedure
  - id: discover
    resource: src/Fabricate/Core/Rpc/Procedures/Server/DiscoverProcedure.php
    title: DiscoverProcedure
  - id: subscriptions
    resource: src/Fabricate/Core/Rpc/Procedures/Resources/SubscriptionsProcedure.php
    title: SubscriptionsProcedure
  - id: state
    resource: src/Fabricate/Mechanism/RequestState.php
    title: RequestState
  - id: tests
    resource: tests/Feature/Stateless/StatelessTest.php
    title: Stateless tests
  - id: spec
    resource: https://modelcontextprotocol.io/specification/2026-07-28
    title: MCP 2026-07-28
---

# Requests

There is no `initialize`: an `MCPServer` in 2026-07-28 answers each request on its own. A request without `_meta.io.modelcontextprotocol/protocolVersion` or `clientCapabilities` is -32602; one naming another version is `UnsupportedProtocolVersionError` (-32022) with the supported and requested versions. `initialize`, `ping`, `logging/setLevel` and `resources/subscribe` are unknown methods (-32601).[^entity][^tests]

The `RequestContext` carries what the request declared: its client capabilities, its `logLevel`, and its progress token.[^entity]

# server/discover and serverInfo

`server/discover` answers the modern versions the configuration supports, its capabilities (tasks appear as the `io.modelcontextprotocol/tasks` extension, see [Tasks](/mechanism/tasks.md)), its `instructions`, and `ttlMs: 0`, `cacheScope: private`. Every 2026-07-28 result carries `_meta.io.modelcontextprotocol/serverInfo` when the configuration names a version.[^discover][^procedure]

# Logging and progress

`log()` on the context sends `notifications/message` only for a request whose `_meta` named a `logLevel`, at or above it, and only if the configuration declares logging. `progress()` sends `notifications/progress` for a request with a progress token.[^server]

# Multi round-trip requests

A tool, prompt or resource returns `InputRequired(requests, state)` to ask for the client's input. The procedure answers `InputRequiredResult`: each request must be of a kind the client declared (`elicitation`, `sampling`, `roots`), or the request is -32021 naming the missing ones. The state goes out as `requestState`, sealed by `RequestState` with an HMAC under the app key and bound to the method, the tool, prompt or URI, and `mcp.request_state.ttl` seconds. On the retry the procedure verifies it (-32602 if it was altered, expired or issued for another request) and passes `$input_responses` and `$request_state` to `handle()` or `read()` by name. Earlier versions have no `input_required`, so asking there is an internal error.[^procedure][^state][^tests]

# subscriptions/listen

The server sends `notifications/subscriptions/acknowledged` first, with the subset it agreed to: list changes for capabilities that declared `listChanged`, the requested resource URIs when it declared `resources.subscribe`, and task ids it knows for a client that declared the tasks extension (asking for task ids without it is -32021). The request stays open; the subscription gets only what it agreed to, each notification carrying `_meta.io.modelcontextprotocol/subscriptionId`. `notifications/cancelled` ends it, and a server that closes sends `notifications/cancelled` for each subscription still open, the one server-sent cancellation 2026-07-28 allows.[^subscriptions][^server][^spec]

[^entity]: Entity
[^server]: MCPServer
[^procedure]: Procedure
[^discover]: DiscoverProcedure
[^subscriptions]: SubscriptionsProcedure
[^state]: RequestState
[^tests]: Stateless tests
[^spec]: MCP 2026-07-28
