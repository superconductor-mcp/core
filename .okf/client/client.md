---
type: Client
title: Client
description: ClientManager, MCPClient, ClientBag, the start node, the handshake, cache refills, statuses, close.
resource: src/Fabricate/Client
tags: [mcp, client, manager, promises]
status: draft
generated: { by: claude-sonnet-5-5/claude-code, at: 2026-10-28T23:00:00Z }
sources:
  - id: client
    resource: src/Fabricate/Client/MCPClient.php
    title: MCPClient
  - id: manager
    resource: src/Fabricate/Client/ClientManager.php
    title: ClientManager
  - id: node
    resource: src/Fabricate/Client/Workflows/ClientStartNode.php
    title: ClientStartNode
---

# Manager

`ClientManager` (`mcp.clients`) reads `mcp.mcp_servers`. `getInstanceConfig($name)` adds `name` and defaults `type` to `stdio` when the entry has a `command`. `resolve()` builds the client through the transport's creator and opens it at once, or `disable()`s it when the entry says `disabled`. `connectAll()` resolves every name; `tools()` merges every connected client's tools as `{server}__{tool}`; `callTool($prefixed, $args)` splits on the first `__`; `closeAll()` starts every close in one frame and waits on the loop (`until()`, which borrows the loop even after it stopped) until each connection is `closed()`.

# Client

`MCPClient($name, $loop)` with a `Connection` attached. Its `ClientBag` holds: `status`, `own_ids` and `their_ids`, `pending` (id key → `[Promise, response class, method]`), `filling` (id key → `[list kind, pages so far]`), the negotiated `protocol_version`, `server_capabilities`, `server_info`, the four caches, `connect_deadline`, `initial_lists`, `error`.

`open()`: status Connecting; `connection->open()` (a throw fails the client); a deadline of `mcp.client.connect_timeout`; an `InitializeRequest` proposing the latest supported version with the client's `entity_info` (`app.name`, `MCPClient::VERSION`) and its `entity_capabilities` (none at 2024-11-05).

`request($request, $responseClass)`: a loop promise, rejected at once with `RequestFailed` unless the status allows it (Connected: anything; Connecting: initialize and ping only), else recorded in `pending` and sent. `ping()`, `listTools()`, `callTool()`, `listResources()`, `listResourceTemplates()`, `readResource()`, `listPrompts()`, `getPrompt()` wrap it; a list helper returns one page.

# The start node

The connection calls `receive($messages)` with each batch of decoded lines; a `Flow` runs `ClientStartNode` over them.

- `ReceivedResponse`: look up `pending`; none → log. An error reply or a `fromReceived()` throw rejects with `RequestFailed` (`rpc_code`, `data`); an `InitializeResponse` failing calls `fail()`; a failing cache fill keeps what was gathered. `fromReceived()` reads content into the server-side primitives through `Schema\Content`, so both sides hold the same types. A good reply resolves the promise, then: an `InitializeResponse` → `handshakeDone()`; a fill page → gather and ask for the next page or hand the list to `filled()`.
- `MCPRequest` from the server: `their_ids->receive()` or `InvalidRequest`; `ping` → `PingResponse`; anything else → `MethodNotFound`.
- `MCPNotification`: `list_changed` → `fill()` that kind; `message` → the application log at the sent level; others logged.
- `JsonRpcError` from the decoder: sent back when it `replies()` (an unknown server request gets its `MethodNotFound`), logged otherwise.

Nothing in the handshake runs inside a promise callback: a throwing `then` callback is swallowed by the promise engine, so the node calls `handshakeDone()`, `fail()`, `fill()` and `filled()` directly.

# Handshake and ready

`handshakeDone()`: a version outside `supported_mcp_versions` fails the client; otherwise record the server's answer, send `notifications/initialized`, status Connected, and `fill()` each list kind the capabilities advertise (`tools`, `resources` plus `resource_templates`, `prompts`). `ready()` resolves with the client when `initial_lists` is empty; with nothing advertised, at once. A fill walks every page with the cursor and replaces the cache atomically; a `list_changed` runs the same fill again.

# Capabilities and elicitation

A request for a method whose capability the server did not declare is rejected before it leaves (`RequestFailed` "did not declare ..."), from the handshake on. `onElicit()` adds the client's one optional capability; see [elicitation](../mechanism/elicitation.md).

# Ending

`close()` is deliberate: status NotConnected, every pending promise rejected, `connection->close()`. `fail($why)` is not: status Error, `error` recorded, the same settling, `ready()` rejected if still pending. `connectionClosed($reason)` from the transport is a failure while Connecting or Connected and nothing after a deliberate close.

# Ready is settled by the client, not read back from the promise

`MCPClient` keeps its own flag for the ready promise. The loop's promise wrapper learns it is settled one queue flush after `resolve()`, so a failure landing in the very next frame (an HTTP server answering `404` to the listening GET right after the lists filled) would otherwise reject a fulfilled promise and throw. Any guard on `ready` reads the client's flag.
