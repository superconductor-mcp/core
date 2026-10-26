---
type: Server
title: Capabilities and notifications
description: MCPServer::notify(), the outbox, advertises(), MCPServerBag::wants().
resource: src/Fabricate/Server/MCPServer.php
tags: [mcp, server, capabilities, notifications]
status: draft
generated: { by: claude-fable/5.1, at: 2026-10-26T04:30:00Z }
sources:
  - id: server
    resource: src/Fabricate/Server/MCPServer.php
    title: MCPServer
  - id: bag
    resource: src/Fabricate/NutsAndBolts/MCPServerBag.php
    title: MCPServerBag
---

# Capabilities are declared, then enforced twice

`$entity_capabilities` is what `initialize` returns; override `capabilities()` to compute it. From then on two filters apply.

**At the server:** `notify($notification)` queues into the outbox only when `advertises($notification)` is true: `tools/list_changed` needs `tools.listChanged`, `resources/list_changed` needs `resources.listChanged`, `prompts/list_changed` needs `prompts.listChanged`, `resources/updated` needs `resources.subscribe`, `message` needs `logging`. `log($level, $data, $logger)` is `notify()` with a `LoggingMessageNotification`.

**At the session:** the transport drains the outbox (`drainNotifications()`) after each response and every frame, and sends each notification only when `MCPServerBag::wants()` agrees: `resources/updated` only for a uri this session subscribed to, `message` only at or above this session's level (`logging/setLevel`, else `mcp.logging.default_level`). Nothing leaves while the session is Idle.

# The running instance

The transport's sketch registers the running server in the container under its own class and `MCPServer::class`. The repository binds servers non-shared, so a tool that asks for its server by type gets the instance that is serving, not a fresh copy.
