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
     * @param string|null $auth_identifier Who the request creating it came from, as the transport authenticated them.
     */
    public function create(?int $ttl = null, ?string $auth_identifier = null): TaskRecord
    {
        $task = new TaskRecord((string) Str::uuid(), $ttl ?? self::DEFAULT_TTL, self::POLL_INTERVAL, auth_identifier: $auth_identifier);

        return $this->tasks[$task->id] = $task;
    }

    /**
     * The task, if whoever asks is whoever created it; anyone else is told of no such task.
     */
    public function find(string $id, ?string $auth_identifier = null): ?TaskRecord
    {
        $this->forgetExpired();

        $task = $this->tasks[$id] ?? null;

        return $task?->auth_identifier === $auth_identifier ? $task : null;
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
