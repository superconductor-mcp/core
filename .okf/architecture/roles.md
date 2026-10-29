---
type: Architecture
title: Roles
description: A server is a sketch run by a transport; a client is an in-process component in the application's loop. What they share and what they never do.
resource: src/Fabricate
tags: [mcp, server, client, loop]
status: draft
generated: { by: claude-sonnet-5-5/claude-code, at: 2026-10-29T00:00:00Z }
sources:
  - id: server
    resource: src/Fabricate/Server/MCPServer.php
    title: MCPServer
  - id: client
    resource: src/Fabricate/Client/MCPClient.php
    title: MCPClient
  - id: entity
    resource: src/Fabricate/Mechanism/MCPEntity.php
    title: MCPEntity
  - id: server-contract
    resource: src/Fabricate/Contracts/Server/MCPServer.php
    title: Contracts\Server\MCPServer
  - id: client-contract
    resource: src/Fabricate/Contracts/Client/MCPClient.php
    title: Contracts\Client\MCPClient
---

# Two entities, one loop

A **server** and a **client** are different things and never the same object. A client is never a server: the spec lets a server send a client requests (ping, and with capabilities, sampling and roots), but answering those is not serving.

- An `MCPServer` is configuration: identity (`entity_info`), capabilities, and the lists of what it offers. It does not run. A transport's sketch (`rocket mcp:cli <server>` in superconductor/stdio) pulls it from the `ServerRepository`, registers the running instance in the container under its class and the `Contracts\Server\MCPServer` contract, and drives the lifecycle around it, one frame at a time.
- An `MCPClient` is a running thing: one connection to one server, living inside the application's own IOPools loop. It never owns a loop. Several connections are several clients; `ClientManager` holds them by name.

Both extend `Mechanism\MCPEntity`, which holds `entity_info`, `entity_capabilities` and `entityInfo()`.

# Contracts

Code outside the two classes types against their contracts, not the classes: `Contracts\Server\MCPServer` and `Contracts\Client\MCPClient`, both extending `Contracts\Mechanism\MCPEntity`. Each declares the public methods that callers outside the class use; construction, `extends` and the client's `LISTS` constant stay on the concrete classes. The server contract declares the lists a session reads, `serverInfo()` and `capabilities()`, `notify()`, `log()` and `drainNotifications()`. The client contract declares the connection lifecycle (`open()`, `close()`, `status()`, `ready()`), the negotiated handshake values, the cached lists and the typed requests (`ping()`, `listTools()`, `callTool()`, `listResources()`, `listResourceTemplates()`, `readResource()`, `listPrompts()`, `getPrompt()`); response classes appear in `@return` docblocks only. What a transport and the start node drive stays off it and on the concrete `Client\MCPClient`: `receive()`, `connectionClosed()`, `fill()`, `filled()`, `handshakeDone()` and `fail()`, so `ClientStartNode`, `FakeConnection` and stdio's `StdioConnection` hold the concrete client.

The enums the contracts name live in `Contracts` too: `Contracts\Client\ClientStatus` and `Contracts\NutsAndBolts\LogLevel`, so the `Contracts` split's manifest needs only PHP and `venusian-voyager/contracts` (`Promise`). `Contracts\Server\MCPServer::notify()` takes `Contracts\NutsAndBolts\MCPNotification`, an `RPCMessage` that declares the `method` property (`public string $method { get; }`) and `toJson()`; `NutsAndBolts\Messages\MCPNotification` implements it through its promoted readonly `method` and its subclasses' `toJson()`. The server's `notify()` and `advertises()`, `MCPServerBag::wants()` and the session's outbox flush (`wants()`, then `toJson()`) all take the contract, so a notification that implements only the contract reaches the wire.

# What they share

Only `Mechanism` and `NutsAndBolts`: the message classes, the definitions, the `ProcedureRouter`. The server routes every request through the router; the client answers the one server request it supports at 2024-11-05 (`ping`) in its own node, because the router's procedures are typed to the server bag.

# Where things live

| Component | Depends on | Holds |
|---|---|---|
| `Contracts` | php, voyager contracts (see above) | interfaces: `Mechanism\MCPEntity`, `Server\MCPServer`, `Client\MCPClient`, `Mechanism\SchemaPrimitive`, `RPCMessage`, `MCPNotification`, `MCPError`, `ToolDefinition`, `Client\Connection`; enums `Client\ClientStatus`, `NutsAndBolts\LogLevel` |
| `NutsAndBolts` | Contracts, voyager nuts-and-bolts, workflows | messages, definitions, schema, `LineDecoder`, `RequestIds`, `Paginator`, `LogLevel`, `ProgressReporter`, `MCPServerBag` |
| `Mechanism` | NutsAndBolts, workflows | `MCPEntity`, `ProcedureRouter`, the procedures, `LogIncomingMessage` |
| `Server` | Mechanism | `MCPServer`, `ServerRepository`, discovery, `ServerServiceProvider` |
| `Client` | Mechanism, io-pools | `MCPClient`, `ClientManager`, `ClientBag`, `ClientStartNode`, `ClientServiceProvider` |
| `Core` | everything | the package provider, config publishing |

Core knows everything; nothing in the package depends on Core.
