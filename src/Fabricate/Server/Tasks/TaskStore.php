<?php

namespace Fabricate\Server\Tasks;

use Voyager\NutsAndBolts\Str;

/**
 * The tasks a server is running or has kept results for, oldest first. Expired tasks are dropped as the store is read.
 */
final class TaskStore
{
    /** How long a task is kept when the client names no ttl: one hour. */
    public const int DEFAULT_TTL = 3_600_000;

    /** How often a client is asked to poll a task, in milliseconds. */
    public const int POLL_INTERVAL = 1_000;

    /** @var array<string, TaskRecord> */
    private array $tasks = [];

    /**
     * @param int|null $ttl The retention the client asked for, in milliseconds; the default when null.
     */
    public function create(?int $ttl = null): TaskRecord
    {
        $task = new TaskRecord((string) Str::uuid(), $ttl ?? self::DEFAULT_TTL, self::POLL_INTERVAL);

        return $this->tasks[$task->id] = $task;
    }

    public function find(string $id): ?TaskRecord
    {
        $this->forgetExpired();

        return $this->tasks[$id] ?? null;
    }

    /**
     * Drops a task, as if it had never been created.
     */
    public function forget(string $id): void
    {
        unset($this->tasks[$id]);
    }

    /**
     * @return list<TaskRecord>
     */
    public function all(): array
    {
        $this->forgetExpired();

        return array_values($this->tasks);
    }

    private function forgetExpired(): void
    {
        $this->tasks = array_filter($this->tasks, fn(TaskRecord $task) => ! $task->expired());
    }
}
