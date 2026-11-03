<?php

namespace Fabricate\Mechanism\Signals;

use Fabricate\Server\Tasks\TaskRecord;

/**
 * Fired whenever a task's status changes. On 2026-07-28, each subscription that named the task gets notifications/tasks.
 */
final readonly class TaskChanged
{
    public function __construct(
        public TaskRecord $task,
    ) {}
}
