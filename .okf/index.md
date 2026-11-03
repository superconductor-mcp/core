---
okf_version: "0.2"
---

# Orientation

* [Package](orientation/package.md) - What superconductor/core holds, how its five components split into their own packages, and how it is tested and released.

# Components

* [Schema](schema/component.md) - One hand-written PHP tree per MCP schema.ts version, plus the code they share in Common.
* [Schema versions](schema/versions.md) - How the five MCP versions differ and where each difference lives in the tree.
* [Contracts](contracts/component.md) - The version-less interfaces and enums every Schema version and later component is written against.
* [Capabilities](mechanism/capabilities.md) - How a concrete server or client configuration produces the capabilities object for a requested protocol version.
* [Procedure routing](mechanism/procedure-routing.md) - How a received MCP request reaches a procedure, and the version, role and capability gates it passes first.
* [Message decoding](mechanism/message-decoding.md) - How an inbound JSON-RPC payload becomes a schema class of whichever protocol version it fits, or an MCPError naming the problem.

# History

* [Update log](log.md) - Dated changes to this package.
