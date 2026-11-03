<?php

namespace Fabricate\Server\Tasks;

use Closure;
use DateTimeImmutable;
use Fabricate\Contracts\Schema\Messaging\MCPError;
use Fabricate\Contracts\Schema\Messaging\Result;
use Fabricate\Contracts\Core\SuperconductorMCPException;
use Fabricate\Contracts\Schema\RPCErrorCode;
use Fabricate\Mechanism\Signals\TaskChanged;
use Fabricate\Schema\V20251125\Concerns\TaskStatus;
use Fabricate\Schema\V20260728\Concerns\TaskStatus as ModernTaskStatus;
use Fabricate\Schema\V20260728\Concerns\InputRequest;
use Fabricate\Schema\V20260728\Messaging\Inputs\InputRequests;
use Fabricate\Schema\V20260728\Concerns\InputResponse;
use Fabricate\Schema\V20260728\Messaging\Inputs\InputResponses;
use Fabricate\Schema\V20260728\Messaging\Tasks\DetailedTask;
use Fabricate\Schema\V20251125\Messaging\Params\TaskStatusNotificationParams;
use Fabricate\Schema\V20251125\Messaging\Tasks\TaskStatusNotification;
use Fabricate\Schema\V20251125\Task;

/**
 * One task: the request it runs, where it stands, and once it settles, the result tasks/result hands back.
 */
final class TaskRecord
{
    public const string WORKING = 'working';
    public const string INPUT_REQUIRED = 'input_required';
    public const string COMPLETED = 'completed';
    public const string FAILED = 'failed';
    public const string CANCELLED = 'cancelled';

    public private(set) string $status = self::WORKING;

    public private(set) ?string $status_message = null;

    public private(set) Result|MCPError|null $result = null;

    public private(set) DateTimeImmutable $updated_at;

    /** @var list<Closure(TaskRecord): void> Called once the task settles. */
    private array $waiters = [];

    /** @var array<string, InputRequest> While input_required, the requests still waiting for an answer, by key. */
    public private(set) array $outstanding = [];

    /** @var array<string, true> Every input request key the task has issued; none is used twice. */
    private array $issued = [];

    /** @var array<string, InputResponse> The answers given so far to the outstanding requests. */
    private array $answered = [];

    /** @var (Closure(InputResponses): void)|null Carries the work on once every outstanding request is answered. */
    private ?Closure $resume = null;

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
        return in_array($this->status, [self::COMPLETED, self::FAILED, self::CANCELLED], true);
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

    /**
     * Moves the task to input_required on 2026-07-28: the requests wait for tasks/update, and once every one is answered
     * $resume carries the work on with the answers. Keys are unique over the task's lifetime.
     *
     * @param array<string, InputRequest> $requests
     * @param Closure(InputResponses): void $resume
     */
    public function requireInput(array $requests, Closure $resume): void
    {
        if($this->terminal()) return;

        foreach(array_keys($requests) as $key) {
            if(isset($this->issued[$key])) {
                throw new SuperconductorMCPException("Task [{$this->id}] already used the input request key [{$key}].", RPCErrorCode::INTERNAL_ERROR->value);
            }

            $this->issued[$key] = true;
        }

        [$this->outstanding, $this->answered, $this->resume] = [$requests, [], $resume];
        $this->moveTo(self::INPUT_REQUIRED, 'Waiting for the client\'s input.');
    }

    /**
     * Takes the answers to outstanding requests from tasks/update, ignoring keys that aren't outstanding. Once every
     * outstanding request is answered, the task is working again and its work resumes.
     */
    public function supply(InputResponses $responses): void
    {
        if($this->status !== self::INPUT_REQUIRED) return;

        foreach($responses->responses as $key => $response) {
            if(isset($this->outstanding[$key])) $this->answered[$key] = $response;
        }

        if(array_diff_key($this->outstanding, $this->answered) !== []) return;

        [$resume, $answers] = [$this->resume, new InputResponses($this->answered)];
        [$this->outstanding, $this->answered, $this->resume] = [[], [], null];

        $this->moveTo(self::WORKING, null);
        $resume($answers);
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
     * The task as the 2026-07-28 tasks extension's DetailedTask: with its outstanding requests, result or error.
     */
    public function toDetailed(): DetailedTask
    {
        return new DetailedTask(
            $this->id,
            ModernTaskStatus::from($this->status),
            $this->created_at->format(DATE_RFC3339_EXTENDED),
            $this->updated_at->format(DATE_RFC3339_EXTENDED),
            $this->ttl,
            $this->status_message,
            $this->poll_interval,
            $this->status === self::INPUT_REQUIRED ? new InputRequests($this->outstanding) : null,
            $this->status === self::COMPLETED ? $this->result : null,
            $this->status === self::FAILED ? $this->result : null,
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

        if(app()->isBound('signals')) signal(new TaskChanged($this));

        if(! $this->terminal()) return;

        [$waiters, $this->waiters] = [$this->waiters, []];

        foreach($waiters as $waiter) $waiter($this);
    }
}
