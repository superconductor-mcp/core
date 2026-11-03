---
type: Module
title: Schema
description: One hand-written PHP tree per MCP schema.ts version, plus the code they share in Common.
resource: src/Fabricate/Schema
tags: [schema, mcp, json-rpc, wire-format]
status: draft
generated: { by: claude-code/claude-opus-5-5, at: 2026-11-01T00:00:00Z }
sources:
  - id: spec
    resource: https://github.com/modelcontextprotocol/modelcontextprotocol/tree/main/schema
    title: MCP schema.ts and schema.json per version, fetched from main, not pinned to a commit
  - id: common
    resource: src/Fabricate/Schema/Common
    title: Common tree
  - id: progress
    resource: src/Fabricate/Schema/V20251125/Messaging/Progress/ProgressNotification.php
    title: A 2025-11-25 message class
  - id: paginated
    resource: src/Fabricate/Schema/V20260728/Messaging/Pagination/PaginatedResult.php
    title: 2026-07-28 PaginatedResult
  - id: error-test
    resource: tests/Feature/V20241105/Messaging/MCPErrorTest.php
    title: MCPError tests
  - id: wire-tests
    resource: tests/Unit
    title: WireFormatTest, one per version
  - id: angel
    resource: Angel's instructions in the 2026-11-01 session
    title: Angel's direction on this component
    author: human:angel
---

# Overview

Each dated tree mirrors that version's `schema.ts`.[^spec] The classes are value objects that serialize to the wire format through `toArray()`, and `Common`'s JSON-RPC envelope adds `toJson()`.[^common] There is no parsing from incoming JSON, and no transport or runtime uses these classes yet.

Every class is written by hand, with its own `toArray()`. No generator or reflective serializer, because classes differ in small ways a script would flatten.[^angel]

# Layout

| Path | Holds | Files |
|------|-------|-------|
| `Common/` | JSON-RPC envelope, base messages, `Icon`, `BaseMetaData`, capability payload trait | 15 |
| `V20241105/` | 2024-11-05 | 67 |
| `V20250326/` | 2025-03-26 | 71 |
| `V20250618/` | 2025-06-18 | 78 |
| `V20251125/` | 2025-11-25 | 128 |
| `V20260728/` | 2026-07-28 | 142 |

Inside a version: `Capabilities/{Resources,Prompts,Roots,Sampling,Tools,Elicitation}`, `Concerns/` and `Messaging/<feature>/`. See [Schema versions](/schema/versions.md) for what each adds.

# Where a type goes

* A concept in more than one version: an interface or enum in [Contracts](/contracts/component.md).
* An interface or enum in one version only: that version's `Concerns/`.
* Concrete code shared unchanged: `Common/`.
* A TypeScript union: a marker interface the members implement, such as `ClientRequest` or `ContentBlock`.
* A spec constant: an enum (`ProtocolVersion`, `RPCErrorCode`, `ElicitationErrorCode`, 2026-07-28's `MCPErrorCode`) or the `"2.0"` literal for `JSONRPC_VERSION`.

# Class style

```php
final readonly class ProgressNotification extends JSONRPCNotification implements ProgressContract, ClientNotification, ServerNotification
{
    public function __construct(ProgressNotificationParams $params)
    {
        parent::__construct("2.0", "notifications/progress", $params);
    }
}
```

* `readonly` throughout. A child doesn't re-promote a parent's property, because the second write fails at runtime.
* Constructor params mapped to wire keys are snake_case. Public promoted properties are camelCase and equal the wire name.
* `if(isset(...))` guards optional keys in `toArray()`.
* A JSON object that may be empty is written `?: (object) []`, so it encodes as `{}` and not `[]`.

PHP has one parent class, so two 2026-07-28 classes pick one spec parent and carry the other's fields, each with a comment saying so: `PaginatedResult` extends `CacheableResult`[^paginated], and `Params/ResourceRequestParams` extends `InputResponseRequestParams`.

# Deviations from the spec

* 2024-11-05 to 2025-06-18 `JSONRPCError` accepts a null `id` for a message whose id couldn't be parsed. `schema.json` requires an id; JSON-RPC and the tests want null.[^error-test]
* Unknown error codes throw `SuperconductorMCPException`, though the spec allows any integer.[^error-test]
* The published 2026-07-28 `schema.json` leaves null and floats out of `JSONValue`, while `schema.ts` allows them. `schema.ts` wins.[^spec][^wire-tests]

# Verification

Measured 2026-11-01:

| Check | Result |
|-------|--------|
| Spec exports with no PHP counterpart, by name, constants excluded | 0 in all five |
| Fixtures validated with ajv against the official `schema.json` | 474 of 474 |
| Classes that load | 581, with Contracts |
| Pest, `tests/` | 142 passed, 2 risky (empty `MCPRequestTest` and `MCPResponseTest`) |

The Pest tests are in the repository. The name check and the ajv harness and fixtures were session scratch files and aren't, so those two figures can't be re-run from here. Not exercised against a real MCP client or SDK.

[^spec]: MCP schema.ts and schema.json per version, fetched from main, not pinned to a commit
[^common]: Common tree
[^paginated]: 2026-07-28 PaginatedResult
[^error-test]: MCPError tests
[^wire-tests]: WireFormatTest, one per version
[^angel]: Angel's direction on this component
