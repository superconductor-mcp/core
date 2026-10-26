---
type: Architecture
title: Roles
description: A server is a sketch run by a transport; a client is an in-process component in the application's loop. What they share and what they never do.
resource: src/Fabricate
tags: [mcp, server, client, loop]
status: draft
generated: { by: claude-fable/5.1, at: 2026-10-26T04:30:00Z }
sources:
  - id: server
    resource: src/Fabricate/Server/MCPServer.php
    title: MCPServer
  - id: client
    resource: src/Fabricate/Client/MCPClient.php
    title: MCPClient
---

# Two entities, one loop

A **server** and a **client** are different things and never the same object. A client is never a server: the spec lets a server send a client requests (ping, and with capabilities, sampling and roots), but answering those is not serving.

- An `MCPServer` is configuration: identity (`entity_info`), capabilities, and the lists of what it offers. It does not run. A transport's sketch (`rocket mcp:cli <server>` in semiconductor/stdio) pulls it from the `ServerRepository`, registers the running instance in the container under its class and `MCPServer::class`, and drives the lifecycle around it, one frame at a time.
- An `MCPClient` is a running thing: one connection to one server, living inside the application's own IOPools loop. It never owns a loop. Several connections are several clients; `ClientManager` holds them by name.

# What they share

Only `Mechanism` and `NutsAndBolts`: the message classes, the definitions, the `ProcedureRouter`. The server routes every request through the router; the client answers the one server request it supports at 2024-11-05 (`ping`) in its own node, because the router's procedures are typed to the server bag.

# Where things live

| Component | Depends on | Holds |
|---|---|---|
| `Contracts` | nothing | interfaces: `Mechanism`, `SchemaPrimitive`, `RPCMessage`, `MCPError`, `ToolDefinition`, `Client\Connection` |
| `NutsAndBolts` | Contracts, voyager nuts-and-bolts, workflows | messages, definitions, schema, `LineDecoder`, `RequestIds`, `Paginator`, `LogLevel`, `ProgressReporter`, `MCPServerBag` |
| `Mechanism` | NutsAndBolts, workflows | `MCPMechanism`, `ProcedureRouter`, the procedures, `LogIncomingMessage` |
| `Server` | Mechanism | `MCPServer`, `ServerRepository`, discovery, provider |
| `Client` | Mechanism, io-pools | `MCPClient`, `ClientManager`, `ClientBag`, `ClientStartNode`, provider |
| `Core` | everything | the package provider, config publishing |

Core knows everything; nothing in the package depends on Core.
