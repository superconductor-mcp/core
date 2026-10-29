---
type: Architecture
title: Transports
description: How a transport package (superconductor/stdio, superconductor/web planned) plugs in: the server sketch through sketches.load, the client creator through mcp.clients.
resource: src/Fabricate/Core/Providers/SuperconductorMCPServiceProvider.php
tags: [mcp, transport, provider]
status: draft
generated: { by: claude-sonnet-5-5/claude-code, at: 2026-10-28T23:00:00Z }
sources:
  - id: provider
    resource: src/Fabricate/Core/Providers/SuperconductorMCPServiceProvider.php
    title: SuperconductorMCPServiceProvider
  - id: manager
    resource: src/Fabricate/Client/ClientManager.php
    title: ClientManager
---

# This package names no transport

`superconductor/core` ships no sketch and no connection. A transport is a separate package that provides both ends: `superconductor/stdio` today, and `superconductor/web` (planned, built on venusian/serve) for HTTP.

# Server end

A transport ships a sketch extending `Fabricate\Core\Runner\Sketches\StartMCPServer` and adds it to the application's sketches in its provider: `$this->app['config']->push('sketches.load', TheSketch::class)`. Rocket discovers sketches only under the application's own paths, so a package's sketch must join this way. The sketch's slug carries its signature: `#[Sketch('mcp:cli {server : The name of the MCP server to start}')]`.

# Client end

`ClientManager` is a `MultipleInstanceManager` with `type` as its driver key. A transport registers a creator for its type in its provider's `boot()`:

```php
$this->app->make(ClientManager::class)->extend('stdio', function (FrameworkCore $app, array $config): ClientContract {
    $loop = $app->make(Loop::class);
    $client = new MCPClient($config['name'], $loop);

    return $client->attach(new StdioConnection($config, $loop, $client));
});
```

The creator gets the `mcp_servers` entry with its `name` added. It builds the client and attaches a `Connection`; the manager opens the client when it is first resolved, unless the entry is `disabled`.

# The Connection contract

`Fabricate\Contracts\Client\Connection`: `open()` starts and joins the loop (throws when it cannot start at all), `send(string $line)` writes one message, `close()` ends in the transport's own order and never blocks, `closed()` is true once fully ended, `describe()` names it for errors. The connection delivers decoded lines by calling `MCPClient::receive(array $messages)` and reports its own end with `MCPClient::connectionClosed(string $reason)`. It never posts to loop mail: a reply from one server must land on its own client.
