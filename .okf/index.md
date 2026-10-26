---
okf_version: "0.2"
---

# superconductor/mcp

* [Roles](architecture/roles.md) - A server is a sketch run by a transport; a client is an in-process component in the application's loop. What they share and what they never do.
* [Transports](architecture/transports.md) - How a `semiconductor/*` package plugs in: the server sketch through `sketches.load`, the client creator through `mcp.clients`.

# Protocol

* [Messages](protocol/messages.md) - Readonly message classes, `generate()` from a line, `toJson()` to the wire, `fromReceived()` from a reply, and the decoder as the one trust boundary.
* [Errors](protocol/errors.md) - The JSON-RPC and MCP error codes, which class carries which, and when nothing is sent.
* [Ids](protocol/ids.md) - The per-requestor rule, two `RequestIds` per side, and why one shared ledger broke SDK clients.

# Server

* [Capabilities and notifications](server/capabilities.md) - `MCPServer::notify()`, the outbox, `advertises()`, `MCPServerBag::wants()`.
* [Definitions](server/definitions.md) - Tools, resources, templates, prompts, completers: the DTOs, the attributes, what `handle()` gets and returns.

# Mechanism

* [Procedures](mechanism/procedures.md) - The router, the procedure per method, the `ASYNC` flag and the fiber it buys.

# Client

* [Client](client/client.md) - `ClientManager`, `MCPClient`, `ClientBag`, the start node, the handshake, cache refills, statuses, close.

# Reference

* [Config](reference/config.md) - Every key in `config/mcp.php` and who reads it.
* [Log](log.md) - Changes that altered behaviour.
