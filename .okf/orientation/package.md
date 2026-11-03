---
type: Package
title: superconductor/core
description: What superconductor/core holds, how its five components split into their own packages, and how it is tested and released.
resource: composer.json
tags: [package, orientation, status, release]
status: draft
generated: { by: claude-code/claude-opus-5-5, at: 2026-11-03T22:00:00Z }
sources:
  - id: manifest
    resource: composer.json
    title: Package manifest
  - id: tree
    resource: src/Fabricate
    title: Source tree, listed 2026-11-03
  - id: split
    resource: .github/split.sh
    title: split.sh, copied unchanged from venusian/surface
  - id: workflows
    resource: .github/workflows
    title: tests.yml and split.yml
  - id: docs
    resource: https://venusian.projectsaturnstudios.com/docs/superconductor/overview
    title: Superconductor MCP documentation, venusian/docs superconductor/
---

# Overview

The Superconductor MCP framework module for VenusianPHP, version 0.11.0, autoloaded as `Fabricate\` from `src/Fabricate/`.[^manifest] It replaces `superconductor/contracts`, `schema`, `mechanism`, `server` and `client`, its five split components, and suggests the `superconductor/stdio` and `superconductor/web` transports.[^manifest] User documentation is the `superconductor/` section of venusian/docs.[^docs]

# Components

| Component | Package | Holds |
|-----------|---------|-------|
| [Schema](/schema/component.md) | `superconductor/schema` | One tree per protocol version, all five built |
| [Contracts](/contracts/component.md) | `superconductor/contracts` | Version-less interfaces, enums and attributes |
| `Mechanism` | `superconductor/mechanism` | [Entities](/mechanism/entities.md), [message decoding](/mechanism/message-decoding.md), the [tool](/mechanism/tools.md), [prompt](/mechanism/prompts.md) and [resource](/mechanism/resources.md) bases |
| `Server` | `superconductor/server` | `MCPServer`, `ServerConfiguration`, the registries and discovery, [tasks](/mechanism/tasks.md) |
| `Client` | `superconductor/client` | `MCPClient`, `ClientConfiguration`, the [client capability](/mechanism/client-capabilities.md) concretes |
| `Core` | none: ships only in `superconductor/core` | `SuperconductorMCPServiceProvider`, the [procedure router](/mechanism/procedure-routing.md) and procedures, the capability tools |

`Core` has no `composer.json`, as `Voyager/Core` and `Surface/Core` have none. `Mechanism\Entity` imports `Core\Routing\ProcedureRouterFacade` and `Client\MCPClient` imports `Core\Rpc\Procedures\ClientCapabilityProcedure`, so those two components run with core installed, as Voyager's Bus and Queue run with the framework.[^tree]

# Release

`tests.yml` runs Pest on PHP 8.4 and 8.5, NTS and ZTS, refusing a PHP below the patched floor or of the wrong thread safety. A `v*` tag runs `split.yml`: the tests, then `split.sh`, which checks every manifest against the tag (version, `branch-alias` `0.11.x-dev`, `^0.11` on its own packages), splits each `src/Fabricate/<Component>` with splitsh-lite and pushes it and the tag to `github.com/superconductor-fabricate/<package>`, using the `SPLIT_TOKEN` secret.[^workflows][^split]

# Tests

`vendor/bin/pest` from the package root. Tests that need an application's `App\Mcp` classes get them from `tests/Fixtures/app`, autoloaded as `App\` in `autoload-dev`.[^manifest]

[^manifest]: Package manifest
[^tree]: Source tree, listed 2026-11-03
[^split]: split.sh, copied unchanged from venusian/surface
[^workflows]: tests.yml and split.yml
[^docs]: Superconductor MCP documentation, venusian/docs superconductor/
