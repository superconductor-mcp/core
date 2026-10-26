---
type: Mechanism
title: Procedures
description: The router, the procedure per method, the ASYNC flag and the fiber it buys.
resource: src/Fabricate/Mechanism/ProcedureRouter.php
tags: [mcp, router, procedures, fibers]
status: draft
generated: { by: claude-fable/5.1, at: 2026-10-26T04:30:00Z }
sources:
  - id: router
    resource: src/Fabricate/Mechanism/ProcedureRouter.php
    title: ProcedureRouter
  - id: procedure
    resource: src/Fabricate/Mechanism/Rpc/Procedures/Procedure.php
    title: Procedure
---

# One procedure per method

`ProcedureRouter::$routes` maps a method to a `Procedure` class: `initialize`, `ping`, `tools/list`, `tools/call`, `resources/list`, `resources/read`, `resources/templates/list`, `resources/subscribe`, `resources/unsubscribe`, `logging/setLevel`, `completion/complete`, `prompts/list`, `prompts/get`. `dispatch($request, $bag)` builds the procedure and returns its response; an unrouted method is `MethodNotFound`.

A procedure's `handle(TypedRequest, MCPServerBag)` returns the typed response or a `JsonRpcError`. It reads the server from `$bag->server_configuration` and session state from the bag.

# ASYNC

`Procedure::ASYNC` is false. `CallToolProcedure`, `ReadResourceProcedure`, `GetPromptProcedure` and `CompleteProcedure` set it true: they run developer code. `ProcedureRouter::runsAsync($request)` reports it, and the transport runs such a procedure through the loop's `async()` in a fiber, answering when the fiber finishes (a throw becomes `InternalError`).

The fiber runs its first stretch at once, so the `ProgressReporter` registered just before is the one the action's constructor receives. From the first time the action waits on the loop (`$loop->until(…)`, `$loop->await(…)`, a promise's `wait()`), the transport keeps reading: pings are answered, `notifications/cancelled` lands while the request is in flight, progress goes out as reported. An action that sleeps with `usleep()` instead blocks the process as before; a wait that uses an arrow function captures its flag by value and never resumes, so waits use closures with `use (&$flag)`.

Lifecycle and list procedures stay synchronous: their order against state changes matters and they do no developer work.
