---
type: Mechanism
title: Session
description: One client's session on the server side, transport-neutral: the lifecycle states, the id ledgers, dispatch sync or in a fiber, respond, cancel, outbox flush. stdio and HTTP both run it.
resource: src/Fabricate/Mechanism/Session/Session.php
tags: [mcp, session, lifecycle, server]
status: draft
generated: { by: claude-fable/5.1, at: 2026-10-26T18:00:00Z }
sources:
  - id: session
    resource: src/Fabricate/Mechanism/Session/Session.php
    title: Session
  - id: bag
    resource: src/Fabricate/Mechanism/Session/SessionBag.php
    title: SessionBag
  - id: states
    resource: src/Fabricate/Mechanism/Session/Lifecycle/LifecycleState.php
    title: LifecycleState
---

# One session, any transport

`Session::handle($message, $send)` takes one decoded message and a closure that writes a JSON line back to the client it came from. The transport owns the wire; the session owns everything the spec says about a session. A transport extends `SessionBag` for what only it needs (stdio: ping timers) and hangs on `onStateChange()` for its timers.

# Lifecycle

| State | Accepts | Leaves on |
|---|---|---|
| Idle | `initialize` only | the `initialize` response |
| Handshake | `initialize`, `ping`, `logging/setLevel` | `notifications/initialized` |
| Available | everything but `initialize` | the transport ending the session |

A refused request gets `InvalidRequest` "Method [x] is not allowed." with its id. A repeated client id gets `InvalidRequest` "has already been used." and touches nothing.

# Dispatch

`ProcedureRouter::runsAsync()` decides: a sync procedure answers inside `handle()`; an `ASYNC` one runs in `$loop->async()` and answers from its `then`. Either way `respond()` drops the reply when `cancelled[id]` was set meanwhile, sends it, moves Idle → Handshake on an `InitializeResponse`, and flushes the server's outbox through the same `$send`. A `ProgressReporter` is registered in the container for the handler's lifetime, sending `notifications/progress` through `$send`.

# Notifications

`bag->notifications` maps a method to an action class with `handle(SessionBag $bag, $notification)`. Shipped in `Session\Actions`: `InitializedAction` (next state), `CancelledAction` (mark `cancelled[id]` when in flight). The transport's config holds the map (`stdio.notifications`, `web.notifications`); an unlisted method is logged and ignored.

# Capability gate

After the lifecycle check, `Capabilities::requiredBy()` names the capability a method needs at the negotiated version; if the server's `capabilities()` does not declare it, the request is answered `MethodNotFound`. 2025-06-18 makes "use only what was negotiated" a MUST on both sides.

# Requests to the client

`Session::ask(MCPRequest, $send)` sends a server-to-client request and parks its id (`Session::key()`, so `"1"` and `1` stay apart) in `SessionBag::$awaiting`. A `ReceivedResponse` given to `handle()` with a parked id lands in `SessionBag::$answers`; one nobody waits for is dropped. [Elicitation](elicitation.md) is the user; it waits on the bag, not on a promise.

# Batches

`SessionBag::takesBatches()` is true below 2025-06-18. A transport asks it before opening a batch and refuses the whole batch otherwise.

# What a transport adds

stdio: `STDIOBag` with `stop_waiting`, `ping_timer`, pings with `own_ids`; `STDIOLoopStartNode` handles `PingDue`, `PipeClosed`, `HandshakeTimedOut` and the ping replies, delegates the rest. HTTP (semiconductor/sse): `HttpSession` routes a POST's replies to that POST and the server's own lines to the listening stream ring; no pings, an idle timeout instead.
