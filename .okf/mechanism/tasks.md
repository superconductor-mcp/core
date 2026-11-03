---
type: Module
title: Tasks
description: How a 2025-11-25 server runs a tool call as a task, keeps its outcome, and answers tasks/get, result, list and cancel.
resource: src/Fabricate/Core/Rpc/Procedures/Tasks/TasksProcedure.php
tags: [tasks, async, 2025-11-25]
status: draft
generated: { by: claude-code/claude-opus-5-5, at: 2026-11-02T00:00:00Z }
sources:
  - id: procedure
    resource: src/Fabricate/Core/Rpc/Procedures/Tasks/TasksProcedure.php
    title: TasksProcedure
  - id: store
    resource: src/Fabricate/Server/Tasks/TaskStore.php
    title: TaskStore
  - id: record
    resource: src/Fabricate/Server/Tasks/TaskRecord.php
    title: TaskRecord
  - id: tests
    resource: tests/Feature/Tasks/TasksTest.php
    title: Task tests
---

# The store

Each server configuration has a `TaskStore`, made the first time a request runs as a task. A `TaskRecord` holds the task's status (`working`, `completed`, `failed`, `cancelled`), its result once settled, and its ttl, the client's or one hour. Expired tasks are dropped as the store is read; clients are asked to poll every second.[^store][^record]

Settling records a result as `completed`, or an error as `failed`; a cancelled task keeps its status and drops what arrives afterwards. Status changes send `notifications/tasks/status` by mail.[^record]

# Calls

All on 2025-11-25, on servers declaring `tasks`:[^procedure][^tests]

* `tasks/get` returns the task. An unknown or expired task is -32602.
* `tasks/result` returns the outcome of the request the task ran, with `_meta.io.modelcontextprotocol/related-task`; a failed request's error comes back as the error. While the task is working the answer waits, and comes by mail once it settles. A cancelled task's result is -32602.
* `tasks/list` pages the tasks, oldest first (`tasks.list`).
* `tasks/cancel` cancels a working task; a task already settled is -32602 (`tasks.cancel`). The work isn't interrupted.

Which calls create tasks is in [Tools](/mechanism/tools.md).

[^procedure]: TasksProcedure
[^store]: TaskStore
[^record]: TaskRecord
[^tests]: Task tests

# The 2026-07-28 tasks extension

From 2026-07-28 tasks are the `io.modelcontextprotocol/tasks` extension. A configuration that runs `tools/call` as tasks declares it under `capabilities.extensions`. The server decides: a tool that requires tasks always runs as one, and one that allows them does for a client whose request declared the extension; a tool that requires them refuses a client that didn't with -32021. The answer is `CreateTaskResult` with `resultType: "task"`. A tool that asks for input as soon as it's called gets `InputRequiredResult` instead, so that exchange ends before any task exists.

`tasks/get` answers the task with the fields its status carries: `inputRequests` while `input_required`, `result` once `completed` (a tool error still completes), `error` once `failed` (JSON-RPC errors only). Input asked for while the task runs moves it to `input_required`; `tasks/update` answers outstanding keys, ignores the rest, and once every key is answered the tool runs again with the answers and its state. `tasks/cancel` is acknowledged with an empty result. There is no `tasks/list` or `tasks/result`. An unknown task is -32602, and a client that didn't declare the extension is -32021. Each status change fires `TaskChanged`, and subscriptions that named the task get `notifications/tasks`.

The 2025-11-25 utility above is unchanged; both use the configuration's `TaskStore`.
