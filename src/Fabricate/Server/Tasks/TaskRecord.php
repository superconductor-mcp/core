<?php

namespace Fabricate\Server\Tasks;

use Closure;
use DateTimeImmutable;
use Fabricate\Contracts\Schema\Messaging\MCPError;
use Fabricate\Contracts\Schema\Messaging\Result;
use Fabricate\Schema\V20251125\Concerns\TaskStatus;
use Fabricate\Schema\V20251125\Messaging\Params\TaskStatusNotificationParams;
use Fabricate\Schema\V20251125\Messaging\Tasks\TaskStatusNotification;
use Fabricate\Schema\V20251125\Task;

/**
 * One task: the request it runs, where it stands, and once it settles, the result tasks/result hands back.
 */
final class TaskRecord
{
    public const string WORKING = 'working';
    public const string COMPLETED = 'completed';
    public const string FAILED = 'failed';
    public const string CANCELLED = 'cancelled';

    public private(set) string $status = self::WORKING;

    public private(set) ?string $status_message = null;

    public private(set) Result|MCPError|null $result = null;

    public private(set) DateTimeImmutable $updated_at;

    /** @var list<Closure(TaskRecord): void> Called once the task settles. */
    private array $waiters = [];

    /**
     * @param int $ttl How long, in milliseconds, the task and its result are kept after it is created.
     * @param int $poll_interval How often, in milliseconds, a client should ask about it.
     */
    public function __construct(
        public readonly string $id,
        public readonly int $ttl,
        public readonly int $poll_interval,
        public readonly DateTimeImmutable $created_at = new DateTimeImmutable(),
    ) {
        $this->updated_at = $this->created_at;
    }

    public function terminal(): bool
    {
        return $this->status !== self::WORKING;
    }

    public function expired(DateTimeImmutable $now = new DateTimeImmutable()): bool
    {
        return ($now->format('Uv') - $this->created_at->format('Uv')) > $this->ttl;
    }

    /**
     * Records the result: completed for a result, failed for an error. A cancelled task keeps its status and drops the result.
     */
    public function settle(Result|MCPError $result): void
    {
        if($this->terminal()) return;

        $this->result = $result;
        $this->moveTo($result instanceof MCPError ? self::FAILED : self::COMPLETED, $result instanceof MCPError ? $result->toArray()['message'] : null);
    }

    public function cancel(): void
    {
        if($this->terminal()) return;

        $this->moveTo(self::CANCELLED, 'Cancelled by the client.');
    }

    /**
     * Calls $waiter once the task settles, or now if it already has.
     *
     * @param Closure(TaskRecord): void $waiter
     */
    public function whenSettled(Closure $waiter): void
    {
        $this->terminal() ? $waiter($this) : $this->waiters[] = $waiter;
    }

    /**
     * The task as 2025-11-25's Task, the only version with tasks.
     */
    public function toSchema(): Task
    {
        return new Task(
            $this->id,
            TaskStatus::from($this->status),
            $this->created_at->format(DATE_RFC3339_EXTENDED),
            $this->updated_at->format(DATE_RFC3339_EXTENDED),
            $this->ttl,
            $this->status_message,
            $this->poll_interval,
        );
    }

    /**
     * The notifications/tasks/status a receiver may send when the task moves.
     */
    public function statusNotification(): TaskStatusNotification
    {
        return new TaskStatusNotification(new TaskStatusNotificationParams($this->toSchema()));
    }

    private function moveTo(string $status, ?string $message): void
    {
        $this->status = $status;
        $this->status_message = $message;
        $this->updated_at = new DateTimeImmutable();

        [$waiters, $this->waiters] = [$this->waiters, []];

        foreach($waiters as $waiter) $waiter($this);
    }
}
