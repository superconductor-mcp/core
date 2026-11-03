---
type: Module
title: Capabilities
description: How a concrete server or client configuration produces the capabilities object for a requested protocol version, and the initialize request or result it opens a session with.
resource: src/Fabricate/Mechanism/EntityConfiguration.php
tags: [capabilities, configuration, server, client, versions, initialize]
status: draft
generated: { by: claude-code/claude-opus-5-5, at: 2026-11-02T00:00:00Z }
sources:
  - id: configuration
    resource: src/Fabricate/Mechanism/EntityConfiguration.php
    title: EntityConfiguration
  - id: factory
    resource: src/Fabricate/Mechanism/CapabilitiesFactory.php
    title: CapabilitiesFactory
  - id: server
    resource: src/Fabricate/Server/ServerConfiguration.php
    title: ServerConfiguration
  - id: client
    resource: src/Fabricate/Client/ClientConfiguration.php
    title: ClientConfiguration
  - id: tests
    resource: tests/Feature/Capabilities/EntityCapabilitiesTest.php
    title: EntityCapabilities tests
  - id: handshake-tests
    resource: tests/Feature/Handshake/InitializeMessagesTest.php
    title: Initialize message tests
---

# Overview

A concrete configuration declares what it can do in wire terms, and `capabilities($protocol_version)` returns that version's `ServerCapabilities` or `ClientCapabilities`.[^configuration]

```php
class EchoServer extends ServerConfiguration
{
    protected bool|array $tools = ['listChanged' => true];
    protected array $supported_protocol_versions = ['2025-06-18', '2026-07-28'];
}
```

# Configuration values

| Value | Meaning |
|-------|---------|
| `false` | Off, at any depth |
| `true` or `[]` | On, written as `{}` |
| `['key' => ...]` | On, with these wire keys, nested as the spec nests them |

A nested key switches on its parents.[^tests] `extensions` and `experimental` have open keys, so their arrays pass through whole.[^configuration]

To compute a capability, override its `{name}Config()` method, which returns an array or null for off.[^server][^client]

# How a version is chosen

1. The version must be in the configuration's `$supported_protocol_versions` and in `mcp.supported_protocol_versions`, or `capabilities()` throws.[^configuration]
2. `CAPABILITY_ARGUMENTS` turns each wire path into the constructor argument that switches it on. A key that isn't in the table throws, so a typo can't vanish.[^server][^client]
3. The factory passes only the arguments that version's constructor declares.[^factory]

The constructors are the single record of what each version has. `tasks` on 2024-11-05 or `sampling.context` on 2025-06-18 are valid configuration that those versions leave out. A new capability needs its constructor argument in each version that has it, and one table row.

The output depends only on the configuration and the version; key order in the configuration doesn't change it.[^tests]

# Initialize messages

`ClientConfiguration::initializeRequest(RequestId $id, ?string $protocol_version = null)` builds the version's concrete `InitializeRequest`, and `ServerConfiguration::initializeResult(string $requested_protocol_version)` builds its concrete `InitializeResult`. Each carries the configuration's capabilities and implementation for that version.[^client][^server]

`handshakeProtocolVersion()` picks the version:[^configuration]

1. No version given: the newest one supported.
2. A version both the configuration and `mcp.supported_protocol_versions` support: that one.
3. Anything else: the newest one supported.

Only versions in `handshakeProtocolVersions()` count. That is every supported version except 2026-07-28, which has no initialize. A configuration left with none throws `MCPMechanismException`.[^handshake-tests]

From 2025-11-25 the request is its own JSON-RPC envelope and carries the id. Earlier versions' requests, and every version's result, are sent inside the version's JSON-RPC envelope with that id.[^client]

[^configuration]: EntityConfiguration
[^factory]: CapabilitiesFactory
[^server]: ServerConfiguration
[^client]: ClientConfiguration
[^tests]: EntityCapabilities tests
[^handshake-tests]: Initialize message tests
