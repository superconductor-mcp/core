---
type: Module
title: Entities
description: How an MCPServer or MCPClient handles every message after the handshake, in its session's protocol version.
resource: src/Fabricate/Mechanism/Entity.php
tags: [entity, server, client, messages, dispatch]
status: draft
generated: { by: claude-code/claude-opus-5-5, at: 2026-11-03T18:00:00Z }
sources:
  - id: entity
    resource: src/Fabricate/Mechanism/Entity.php
    title: Entity
  - id: server
    resource: src/Fabricate/Server/MCPServer.php
    title: MCPServer
  - id: tests
    resource: tests/Feature/Entity/MCPServerTest.php
    title: MCPServer tests
  - id: client
    resource: src/Fabricate/Client/MCPClient.php
    title: MCPClient
  - id: client-tests
    resource: tests/Feature/Entity/MCPClientTest.php
    title: MCPClient tests
  - id: envelope
    resource: src/Fabricate/Mechanism/Envelope.php
    title: Envelope
  - id: mail
    resource: src/Fabricate/Mechanism/Mail/OutboundMessage.php
    title: OutboundMessage
  - id: context
    resource: src/Fabricate/Mechanism/RequestContext.php
    title: RequestContext
  - id: async-tests
    resource: tests/Feature/Tasks/TasksTest.php
    title: Task and async call tests
---

# Overview

An entity is built with its configuration and the protocol version its session settled on, and handles each decoded message with `receiveMessage()`. The transport does the handshake, then hands every message to the entity and sends whatever it returns.[^entity]

```php
$server = new MCPServer($server_configuration, '2025-06-18');

if($reply = $server->receiveMessage($decoded)) $pipe->send($reply->toJson());
```

# Message flow

1. `reconcileMessage()` hydrates the message with `MessageHydrator::hydrateAs()` in the session version. A response or error response takes the method from `$pending_requests`, which it removes; a response to no request of the entity's fails hydration and is dropped.[^entity]
2. A request goes to `receiveRequest()`, which returns a `Result` or `MCPError`; `encodeMessage()` wraps it with the request's id in that version's JSON-RPC response or error class. A request that fails to hydrate is answered with the hydration error.
3. A notification goes to `receiveNotification()`, a response or error response to `receiveResponse($id, $method, $response)` with the id and method of the request it answers. Neither is answered, valid or not, and `receiveMessage()` returns null.[^tests]

# Sending

`request($request, $id)` wraps a request in the version's JSON-RPC request with `Envelope::request()`, records its method in `$pending_requests` by id, queues it and returns the id. From 2025-11-25 a request class carries its own id; earlier versions take `$id`, and leaving it out throws. `notify($notification)` queues a notification in the version's envelope. The transport drains the queue as it does replies.[^entity][^envelope]

`dispatch()` routes a request through the procedure router with the entity's configuration, so state a procedure stores, such as the log level, lands on that configuration. A refusal the router throws becomes the error.[^entity]

# Server

`MCPServer` refuses `initialize` with -32600 before hydrating it, so an initialize naming another version gets the same answer as one naming the session's.[^server][^tests]

# Client

`MCPClient` is built by the client's transport once its handshake is done: an `MCPServerDefinition` from `mcp.mcp_servers` connects a `ClientConfiguration`, returning a `ClientConnection` whose `client()` is the `MCPClient`, or null with the reason in `failure()`. It is built in the version it agreed on; on 2026-07-28, which has none, it is built in that version and each request it sends carries its `_meta` as built. A server request goes through `dispatch()` to the client-side procedures, and the client returns the result like a server; see [Client capabilities](/mechanism/client-capabilities.md). `request($request, $id, $then)` takes an optional closure, which gets the response's typed result or `MCPError`; a response nothing waits on fires `ResponseReceived` (session id, request id, method, response). Every notification fires `NotificationReceived`. The application decides whether and when to react.[^client][^client-tests]

`MCPServer::receiveResponse()` gets the same id and method; it does nothing yet, since the server sends no requests until client capabilities are built.[^server]

# Queue and mail

Everything an entity sends goes through its queue: `queue()` adds a message, `pullQueued()` hands them all over, oldest first. A transport puts each reply from `receiveMessage()` on the queue, and drains it after it has handled the turn's mail.[^entity]

Work that finishes in a later turn arrives as mail. `OutboundMessage` carries the session id, the protocol version, and the answer or notification; `message()` wraps it in the version's JSON-RPC class for the queue. The transport queues mail whose `session_id` matches its entity's.[^mail]

# Async answers

Each entity has a `session_id`, a UUID unless one is given. For every request `receiveMessage()` builds a `RequestContext` (session id, request id, version) and passes it to the procedure as an optional fourth argument. A procedure whose work runs async returns `Deferred`, and the entity sends nothing for the request; the work later calls `RequestContext::reply()`, which posts an `OutboundMessage` to the loop with the request's id.[^context][^async-tests]

# Change signals

A tool, prompt or resource can ask for the `RequestContext` in `handle()` or `read()`. `log($level, $data, $logger)` fires a `LogMessage` signal, which the session's `MCPServer` sends as `notifications/message` if the configuration declares logging and the level is at or above the one set with `logging/setLevel` (every level until one is set). `progress($progress, $total, $message)` sends `notifications/progress` when the request carried `_meta.progressToken`; 2024-11-05 has no `message`. Both are marked with the request they belong to.[^server]

`notifications/cancelled` cancels a request whose answer is still coming: the `Entity` keeps each deferred request's `Cancellation`, and once cancelled the context sends nothing more for it, neither its answer nor notifications. `cancelled()` tells the work so it can stop. A request already answered, or unknown, is left alone; a 2025-11-25 cancel without `requestId` is for tasks, which `tasks/cancel` stops.[^server]

`MCPServer` listens for three signals, `LogMessage` among them. `ResourceUpdated` sends `notifications/resources/updated` if the session subscribed to the URI; `ListChanged` sends the tools, prompts or resources `list_changed` notification if the configuration declared `listChanged` for it. Both go out as mail, so they reach the client even when nothing else is happening. A signal with a session id reaches only that session; Each signal dispatcher carries one listener per signal, which walks the sessions still open on it; `close()` takes a server off that list, so an ended session leaves nothing in the dispatcher.[^server]

[^entity]: Entity
[^server]: MCPServer
[^tests]: MCPServer tests
[^client]: MCPClient
[^client-tests]: MCPClient tests
[^envelope]: Envelope
[^mail]: OutboundMessage
[^context]: RequestContext
[^async-tests]: Task and async call tests
