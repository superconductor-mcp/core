---
type: Reference
title: Schema versions
description: How the five MCP versions differ and where each difference lives in the tree.
resource: src/Fabricate/Schema
tags: [schema, mcp, versions]
status: draft
generated: { by: claude-code/claude-opus-5-5, at: 2026-11-01T00:00:00Z }
sources:
  - id: spec
    resource: https://github.com/modelcontextprotocol/modelcontextprotocol/tree/main/schema
    title: MCP schema.ts per version, fetched from main, not pinned to a commit
  - id: tree
    resource: src/Fabricate/Schema
    title: Version trees, listed 2026-11-01
  - id: wire-2026
    resource: tests/Unit/V20260728/Messaging/WireFormatTest.php
    title: 2026-07-28 wire format tests
  - id: wire-2025-11
    resource: tests/Unit/V20251125/Messaging/WireFormatTest.php
    title: 2025-11-25 wire format tests
---

# Two message shapes

Each tree follows its `schema.ts`.[^spec]

**2024-11-05, 2025-03-26, 2025-06-18:** messages are flat. They have no id and no Params classes, and `RPC\JSONRPCRequest` or `JSONRPCNotification` wraps them.[^tree]

**2025-11-25, 2026-07-28:** messages extend JSON-RPC directly and take a class from `Messaging/Params`.[^tree][^wire-2025-11]

# What each version adds

| Version | Namespace | Changes |
|---------|-----------|---------|
| 2024-11-05 | `V20241105` | Baseline |
| 2025-03-26 | `V20250326` | `AudioContent`; `RPC/JSONRPCBatchRequest` and `JSONRPCBatchResponse` |
| 2025-06-18 | `V20250618` | Batches gone; `Messaging/Elicitation`; `ResourceLink`; tool `outputSchema`; `title` on `Implementation` |
| 2025-11-25 | `V20251125` | `Messaging/Params`; `Messaging/Tasks` with root `Task`, `TaskMetadata` and `RelatedTaskMetadata`; `Messaging/Errors`; icons; `RPC/JSONRPCResultResponse` and `JSONRPCErrorResponse`; `ToolUseContent` |
| 2026-07-28 | `V20260728` | See below |

# 2026-07-28

`Messaging/Initialize`, `Messaging/Ping` and `Messaging/Tasks` are gone, and `Messaging/Discover` (`server/discover`) arrives.[^tree] Every request carries protocol metadata in `_meta`, through `MetaObject`s.[^wire-2026]

* Results carry `resultType`: this version's `Result` extends `Common`'s and adds it, and its own `EmptyResult` extends that.[^tree][^wire-2026]
* `CacheableResult` adds `ttlMs` and `cacheScope`; `*ResultResponse` classes wrap results.[^wire-2026]
* `Messaging/Inputs`: `InputRequiredResult`. Sampling and roots results implement `InputResponse`, not `Result`, and write no `resultType`.[^wire-2026]
* `ProgressNotification` is server-only.[^wire-2026]
* `JSONValue`, `JSONObject` and `JSONArray` are concrete classes at the version root.[^tree]

[^spec]: MCP schema.ts per version, fetched from main, not pinned to a commit
[^tree]: Version trees, listed 2026-11-01
[^wire-2026]: 2026-07-28 wire format tests
[^wire-2025-11]: 2025-11-25 wire format tests
