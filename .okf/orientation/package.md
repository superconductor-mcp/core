---
type: Package
title: superconductor/core
description: What superconductor/core holds today; Schema and Contracts are built, the runtime components are not.
resource: composer.json
tags: [package, orientation, status]
status: draft
generated: { by: claude-code/claude-opus-5-5, at: 2026-11-01T00:00:00Z }
sources:
  - id: manifest
    resource: composer.json
    title: Package manifest
  - id: tree
    resource: src/Fabricate
    title: Source tree, listed 2026-11-01
  - id: logging-plan
    resource: ../../docs/superpowers/plans/2026-10-31-mcp-logging.md
    title: MCP logging implementation plan
---

# Overview

The Superconductor MCP framework module for VenusianPHP, version 0.11.0, autoloaded as `Fabricate\` from `src/Fabricate/`.[^manifest] It replaces `superconductor/contracts`, `schema`, `nuts-and-bolts`, `mechanism`, `server` and `client`.[^manifest]

# Components

| Component | State |
|-----------|-------|
| [Schema](/schema/component.md) | Built for all five versions |
| [Contracts](/contracts/component.md) | `Contracts/Schema` built, plus `Contracts/Core/SuperconductorMCPException` |
| [Capabilities](/mechanism/capabilities.md) | Built: configurations produce a version's capabilities object |
| `Mechanism`, `Server`, `Client` | Otherwise skeletons: `MCPServer`, `MCPClient` and the sessions have no behavior yet[^tree] |
| `Contracts/{Mechanism,Server,Client}` | Marker interfaces and `capabilities()`[^tree] |
| [Procedure routing](/mechanism/procedure-routing.md) | Router and gates built; procedure bodies are stubs |
| `Core` | `SuperconductorMCPServiceProvider`, which merges `config/mcp.php` and loads routes[^tree] |

# Next

The logging plan covers `logging/setLevel` and `notifications/message` on server and client over both transports.[^logging-plan] It is not started, and it was written against an earlier tree: it names `Core/Procedures/Server/LoggingProcedure.php` and `Mechanism/LineDecoder.php`, and a baseline of 48 passing tests, none of which exist now.[^logging-plan][^tree]

# Tests

Run from `apps/devbox` with `./vendor/bin/pest superconductor/core/tests`.[^logging-plan] The package has no `phpunit.xml` of its own.

[^manifest]: Package manifest
[^tree]: Source tree, listed 2026-11-01
[^logging-plan]: MCP logging implementation plan
