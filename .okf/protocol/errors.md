---
type: Protocol
title: Errors
description: The JSON-RPC and MCP error codes, which class carries which, and when nothing is sent.
resource: src/Fabricate/NutsAndBolts/Messages/Errors
tags: [mcp, json-rpc, errors]
status: draft
generated: { by: claude-fable/5.1, at: 2026-10-26T04:30:00Z }
sources:
  - id: errors
    resource: src/Fabricate/NutsAndBolts/Messages/Errors/JsonRpcError.php
    title: JsonRpcError
---

# Classes and codes

All extend `JsonRpcError`, itself an `MCPResponse` and an `MCPError`.

| Class | Code | When |
|---|---|---|
| `ParseError` | -32700 | the line is not JSON; id is null |
| `InvalidRequest` | -32600 | not a request object, wrong `jsonrpc`, bad id, a repeated id, a request the lifecycle state refuses, a response with both result and error |
| `MethodNotFound` | -32601 | an unrouted or unknown method; the client answers an unsupported server request with it |
| `InvalidParams` | -32602 | `generate()` found a wrong shape; a missing or unknown tool argument; a bad cursor |
| `ToolCallError` | -32602 | `tools/call` named a tool not offered (the 2024-11-05 code) |
| `ResourceNotFound` | -32002 | `resources/read` or `subscribe` named no resource or template |
| `InternalError` | -32603 | an action missing or throwing where the result cannot carry it, a fiber failing |
| `UnknownNotification` | -32601 | an unknown notification; `replies()` is false, it is never sent |

# Reuse, do not add

A refusal an existing class can carry with its `data` text reuses that class. No lifecycle, state or "not initialized" error classes.

# What is never answered

A notification, malformed or unknown, gets no reply. On the server the start node sends a decoder error only when `replies()` is true; the client node does the same. A tool that throws reports it in `CallToolResponse` with `isError`, never as a JSON-RPC error. A request the client cancelled gets no response at all.
