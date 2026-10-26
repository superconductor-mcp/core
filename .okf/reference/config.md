---
type: Reference
title: Config
description: Every key in config/mcp.php and who reads it.
resource: config/mcp.php
tags: [mcp, config]
status: draft
generated: { by: claude-fable/5.1, at: 2026-10-26T04:30:00Z }
sources:
  - id: config
    resource: config/mcp.php
    title: config/mcp.php
---

| Key | Default | Read by |
|---|---|---|
| `servers.default_discovery_namespace` | `App\Mcp\Servers` | `ServerServiceProvider`, which maps it to a path under the application namespace and registers each `#[Server]` class |
| `servers.supported_mcp_versions` | `['2024-11-05']` | `InitializeProcedure` (echo the client's version when supported, else offer the latest); `MCPClient` (propose the latest, refuse an answer outside the list) |
| `mcp_servers` | `[]` | `ClientManager`: name → `type`, `command`, `args`, `env`, `cwd`, `disabled` |
| `client.connect_timeout` | `20` | `MCPClient::open()` deadline |
| `pagination.page_size` | `50` | the four list procedures |
| `logging.default_level` | `info` | `MCPServerBag::logLevel()` until `logging/setLevel` |
| `progress.min_interval` | `0.1` | the transport building a `ProgressReporter` |
| `completion.rate_limit.max` / `.per_seconds` | `10` / `1` | `CompleteProcedure` |

A published copy (`vendor:publish --tag=mcp-config`) overrides the merged defaults entirely, so a key added here is added to every published copy too.
