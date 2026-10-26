# Log

## 2026-10-26

* **Client over stdio**: `ClientManager`, `MCPClient`, `ClientBag`, `ClientStartNode`, the `Connection` contract; requests gained `toJson()`, responses `fromReceived()`, the decoder the server-to-client notifications ([client](client/client.md), [messages](protocol/messages.md)). Proven against the dev app's concept server and neo4j-mcp 1.5.3.
* **Two id ledgers per side** replace the server's shared ledger; server pings back on ([ids](protocol/ids.md)).
* **Decoder errors keep their id and code** on the way out; unknown notifications are never answered; every method message must carry `jsonrpc: "2.0"` ([errors](protocol/errors.md), [messages](protocol/messages.md)).
* **`Procedure::ASYNC`**: tool calls, resource reads, prompt gets and completion run in a loop fiber; cancellation and pings work mid-flight ([procedures](mechanism/procedures.md)).
* **Caches hold every page**; the handshake moved out of promise callbacks into the node ([client](client/client.md)).
* Pest suite: 39 tests, 159 assertions. Split manifests filled; README, SECURITY, AGENTS, CLAUDE, CI added.

## 2026-10-25

* Server side of 2024-11-05 complete over stdio: lifecycle, tools, resources, templates, subscriptions, prompts, completion, logging, pagination, progress, cancellation, list_changed, server pings.
