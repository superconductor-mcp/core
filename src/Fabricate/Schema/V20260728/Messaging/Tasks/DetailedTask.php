<?php

namespace Fabricate\Schema\V20260728\Messaging\Tasks;

use Fabricate\Contracts\Core\SuperconductorMCPException;
use Fabricate\Schema\V20260728\Concerns\TaskStatus;
use Fabricate\Schema\V20260728\Messaging\Inputs\InputRequests;
use Fabricate\Schema\V20260728\Messaging\MCPError;
use Fabricate\Schema\V20260728\Messaging\Result;
use Voyager\Contracts\NutsAndBolts\Arrayable;

/**
 * A task with the fields its status carries: inputRequests while input_required, the result once completed, the
 * JSON-RPC error once failed. tasks/get answers with it, and notifications/tasks carries it.
 */
readonly class DetailedTask implements Arrayable
{
    /**
     * @param string $createdAt ISO 8601.
     * @param string $lastUpdatedAt ISO 8601.
     * @param int|null $ttlMs Milliseconds from creation the task is kept; null for unlimited.
     */
    public function __construct(
        public string $taskId,
        public TaskStatus $status,
        public string $createdAt,
        public string $lastUpdatedAt,
        public ?int $ttlMs,
        public ?string $statusMessage = null,
        public ?int $pollIntervalMs = null,
        public ?InputRequests $inputRequests = null,
        public ?Result $result = null,
        public ?MCPError $error = null,
    ) {
        $expected = match($status) {
            TaskStatus::INPUT_REQUIRED => 'inputRequests',
            TaskStatus::COMPLETED => 'result',
            TaskStatus::FAILED => 'error',
            default => null,
        };

        foreach(['inputRequests', 'result', 'error'] as $field) {
            if(isset($this->{$field}) !== ($field === $expected)) {
                throw new SuperconductorMCPException(static::class."- a {$status->value} task ".($field === $expected ? 'needs' : 'has no')." {$field}.");
            }
        }
    }

    public function toArray(): array
    {
        $results = [
            'taskId' => $this->taskId,
            'status' => $this->status->value,
            'createdAt' => $this->createdAt,
            'lastUpdatedAt' => $this->lastUpdatedAt,
            'ttlMs' => $this->ttlMs,
        ];

        if(isset($this->statusMessage)) $results['statusMessage'] = $this->statusMessage;
        if(isset($this->pollIntervalMs)) $results['pollIntervalMs'] = $this->pollIntervalMs;
        if(isset($this->inputRequests)) $results['inputRequests'] = $this->inputRequests->toArray() ?: (object) [];
        if(isset($this->result)) $results['result'] = $this->result->toArray();
        if(isset($this->error)) $results['error'] = $this->error->toArray();

        return $results;
    }
}
