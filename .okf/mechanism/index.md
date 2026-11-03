# Mechanism

* [Capabilities](capabilities.md) - How a concrete server or client configuration produces the capabilities object for a requested protocol version, and the initialize request or result it opens a session with.
* [Procedure routing](procedure-routing.md) - How a received MCP request reaches a procedure, and the version, role and capability gates it passes first.
* [Message decoding](message-decoding.md) - How an inbound JSON-RPC payload becomes a schema class of whichever protocol version it fits, or an MCPError naming the problem.
* [Entities](entities.md) - How an MCPServer or MCPClient handles every message after the handshake, in its session's protocol version.
* [Client capabilities](client-capabilities.md) - How an MCPClient answers roots, sampling and elicitation from the developer's concretes, runs them as 2025-11-25 tasks, and fulfils 2026-07-28 input requests.
* [Tools](tools.md) - How a server's tools are discovered, registered, defined from their class, and listed with tools/list and run with tools/call.
* [Prompts](prompts.md) - How a server's prompts are discovered, registered, defined from their class, listed with prompts/list, filled with prompts/get and completed with completion/complete.
* [Resources](resources.md) - How a server's resources and resource templates are defined, discovered, listed, read, completed and subscribed to.
* [Tasks](tasks.md) - How a server runs a tool call as a task: 2025-11-25's tasks utility, and 2026-07-28's tasks extension.
* [Stateless servers](stateless.md) - How an MCPServer answers 2026-07-28 requests with no handshake: per-request _meta, discover, logging, progress, multi round-trip requests and subscriptions.
