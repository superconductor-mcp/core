---
type: Module
title: Procedure routing
description: How a received MCP request reaches a procedure, and the version, role and capability gates it passes first.
resource: src/Fabricate/Core/Routing/ProcedureRouter.php
tags: [routing, procedures, versions, capabilities]
status: draft
generated: { by: claude-code/claude-opus-5-5, at: 2026-11-01T00:00:00Z }
sources:
  - id: router
    resource: src/Fabricate/Core/Routing/ProcedureRouter.php
    title: ProcedureRouter
  - id: provider
    resource: src/Fabricate/Core/Providers/SuperconductorMCPServiceProvider.php
    title: SuperconductorMCPServiceProvider
  - id: routes
    resource: routes/mcp.php
    title: Package routes
  - id: tests
    resource: tests/Feature/Routing/ProcedureRouterTest.php
    title: ProcedureRouter tests
  - id: tasks
    resource: https://raw.githubusercontent.com/modelcontextprotocol/modelcontextprotocol/main/docs/specification/2025-11-25/basic/utilities/tasks.mdx
    title: MCP 2025-11-25 tasks specification
  - id: spec
    resource: https://github.com/modelcontextprotocol/modelcontextprotocol/tree/main/schema
    title: MCP schema.ts per version, fetched from main, not pinned to a commit
---

# Overview

`ProcedureRouter` is a container singleton. `ProcedureRouterFacade` is a plain static class that forwards to it, with no dependency on serve's facades or web router.[^router]

```php
ProcedureRouterFacade::dispatch($protocol_version, $request, $entity_configuration);
```

`$entity_configuration` is the entity receiving the request, so a server passes its `ServerConfiguration` for what a client sent. On 2024-11-05 to 2025-06-18, `dispatch` takes either the flat message or its `JSONRPCRequest` envelope, and procedures always receive the flat message.

# Gates, in order

Each refusal throws `SuperconductorMCPException`, with the codes under [Errors](#errors).[^router]

| Gate | Source | Refuses |
|------|--------|---------|
| Route | `routes/mcp.php` | An unknown method, including `initialize` |
| Version | `#[CompatibleVersions]` on the method, else the class | `ping` on 2026-07-28; `tasks/*` off 2025-11-25; no attribute at all |
| Role | `ClientRequest` or `ServerRequest` marker on the request | A client sending `sampling/createMessage`; a server sending `tools/call` |
| Configuration version | `capabilities($protocol_version)` | A version this configuration doesn't support |
| Capability | `#[RequiresCapability]` on the method, else the class | `tools/list` with `tools` off; `resources/subscribe` without `resources.subscribe` |

A capability the version can't declare isn't withheld: 2024-11-05 has no `completions` capability but does have `completion/complete`, so it passes.[^tests]

Available methods per version match each `schema.ts` `ClientRequest` and `ServerRequest` union, minus `initialize`.[^spec] Notifications aren't routed.

# Response check

`dispatch` returns a procedure's answer only if all of these hold:[^router]

* It's a `Result` from the requested version's schema tree. A `Common` result counts only if that version has no class of its own, so `Common\Messaging\EmptyResult` passes on 2024-11-05 and fails on 2026-07-28.
* It's a result that spec method can answer with, so a replaced `tools/list` route can't return a `CallToolResult`.
* A `CreateTaskResult` also needs a request with `task` metadata and the matching `tasks.requests.*` capability. A receiver without that capability must process the request normally, ignoring the metadata.[^tasks]

# Errors

`dispatch` throws for a call it refuses, and returns the version's `MCPError` once a procedure has run.

| Situation | Outcome |
|-----------|---------|
| Configuration doesn't support the version | Throws -32602 on a handshake version, -32022 otherwise; checked before anything else, and `supportedProtocolVersions()` gives the list for the error |
| No route, wrong version, wrong role, capability off | Throws -32601 |
| Request object from another version's schema | Throws -32600 |
| Broken route, unknown capability key | Throws -32603 |
| Procedure throws `SuperconductorMCPException` with a code the version has | Returns that code and message, unreported |
| Any other exception, or a response that fails the check | Reported through `report()`; returns -32603 `Internal error` |
| `CancelledException` | Rethrown, so the event loop can unwind the fiber |

# Routes

The provider loads the app's `routes/mcp.php`, or the package's if the app has none. An app file replaces the package routes entirely.[^provider] `computer vendor:publish --tag=mcp-routes` copies the package file to the app's `routes/mcp.php`.

The default routes name a class for `handle`, or `Class@method`. A procedure with one method calls it `handle`, and every method takes the request, the entity configuration and the protocol version, which it uses to build that version's result class. [^routes]

# State

`PingProcedure` answers with `Common\Messaging\EmptyResult`. `LoggingProcedure` stores the request's `level()` with `setLogLevel()` on the `ServerConfiguration` it was dispatched with, then answers the same way; `logLevel()` returns it, or null until a client sets one. That level is the only state a configuration holds, so a procedure that sends logs reads it from the configuration it is given. The other procedure bodies are stubs. A call that passes every gate currently reports a `TypeError` for the missing return value and returns -32603.[^tests]

[^router]: ProcedureRouter
[^provider]: SuperconductorMCPServiceProvider
[^routes]: Package routes
[^tests]: ProcedureRouter tests
[^spec]: MCP schema.ts per version, fetched from main, not pinned to a commit
[^tasks]: MCP 2025-11-25 tasks specification
