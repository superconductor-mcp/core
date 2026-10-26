---
type: Protocol
title: Ids
description: The per-requestor rule, two RequestIds per side, and why one shared ledger broke SDK clients.
resource: src/Fabricate/NutsAndBolts/RequestIds.php
tags: [mcp, json-rpc, ids]
status: draft
generated: { by: claude-fable/5.1, at: 2026-10-26T04:30:00Z }
sources:
  - id: ids
    resource: src/Fabricate/NutsAndBolts/RequestIds.php
    title: RequestIds
  - id: spec
    resource: https://modelcontextprotocol.io/specification/2024-11-05/basic/messages
    title: Messages, 2024-11-05
---

# The rule

The spec: "The request ID MUST NOT have been previously used by the requestor within the same session." The rule is per side. The client may not repeat its own ids; the server may not repeat its own; the two sides may use the same numbers.

# Two ledgers per side

`RequestIds` keeps a set of seen ids (int and string kept apart: `1` and `"1"` differ) and the greatest int. `receive($id)` is true the first time, false on a repeat. `next()` is the greatest int seen plus one, or 0. `forget()` clears both.

Each side holds two: `their_ids`, fed by `receive()` for every request the other side sends, a repeat refused with `InvalidRequest` and no state change; `own_ids`, fed by `next()` for every request this side sends. On the server they live on the transport's bag (`STDIOBag`); on the client on `ClientBag`.

# Why not one shared ledger

The server had one ledger for both directions until 2026-10-26. SDK clients (TypeScript, Python, Go) number their own requests from 0 and never look at the server's. The Inspector sent `initialize` as 0, the server pinged as 1 (greatest seen plus one), the Inspector sent `tools/list` as 1 and was refused. Server pings had to be turned off. Two ledgers follow the spec's wording and turned them back on.

# Responses never touch a ledger

A reply carries the id of a request this side already recorded. The client matches replies against its pending table; the server against its pending pings. An id nobody is waiting for is logged and ignored.
