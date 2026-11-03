---
type: Module
title: Contracts
description: The version-less interfaces and enums every Schema version and later component is written against.
resource: src/Fabricate/Contracts
tags: [contracts, schema, interfaces]
status: draft
generated: { by: claude-code/claude-opus-5-5, at: 2026-11-01T00:00:00Z }
sources:
  - id: tree
    resource: src/Fabricate/Contracts
    title: Contracts tree, listed 2026-11-01
---

# Overview

`Contracts/Schema` holds 80 files naming what is true across MCP versions; [Schema](/schema/component.md) implements them per version.[^tree] `Contracts/Core/SuperconductorMCPException` is the only other file so far. `Contracts/{Mechanism,Server,Messaging,Client}` are empty.[^tree]

# Contracts/Schema

| Path | Holds |
|------|-------|
| `Capabilities/` | `Tool`, `Prompt`, `Resource`, `ResourceTemplate`, `EmbeddedResource`, `Root`, `SamplingMessage` |
| `Contracts/` | `Implementation`, `ClientCapabilities`, `ServerCapabilities`, `Annotated`, `Icon`, content types |
| `Concerns/` | `Role` |
| `Messaging/` | Base `Request`, `Notification`, `Result`, `MCPError`, and the `Client`/`Server` `Request`/`Notification`/`Result` union markers |
| `Messaging/<feature>/` | One interface per message |
| Root | Enums `ProtocolVersion`, `RPCErrorCode`, `ElicitationErrorCode`, `LoggingLevel`, `ElicitAction`, `IncludeContext`, `ToolChoiceMode`, `IconTheme`, and value types `Cursor`, `ProgressToken`, `RequestId` |

An interface that serializes extends `Voyager\Contracts\NutsAndBolts\Arrayable`.[^tree]

[^tree]: Contracts tree, listed 2026-11-01
