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
