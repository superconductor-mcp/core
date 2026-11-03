<?php

namespace Fabricate\Schema\V20260728\Messaging\Tasks;

use Fabricate\Contracts\Schema\Messaging\ServerResult;
use Fabricate\Contracts\Schema\Messaging\Tasks\CreateTaskResult as CreateTaskContract;
use Fabricate\Schema\V20260728\Concerns\ResultTypeEnum;
use Fabricate\Schema\V20260728\Concerns\TaskStatus;
use Fabricate\Schema\V20260728\Messaging\MetaObjects\ResultMetaObject;
use Fabricate\Schema\V20260728\Messaging\Result;

/**
 * The task handle a server answers a request with, in place of its standard result, when it runs it as a task. Its
 * resultType is "task".
 */
final readonly class CreateTaskResult extends Result implements CreateTaskContract, ServerResult
{
    public function __construct(
        public string $taskId,
        public TaskStatus $status,
        public string $createdAt,
        public string $lastUpdatedAt,
        public ?int $ttlMs,
        public ?string $statusMessage = null,
        public ?int $pollIntervalMs = null,
        ?ResultMetaObject $_meta = null,
    ) {
        parent::__construct(ResultTypeEnum::TASK, $_meta);
    }

    public function toArray(): array
    {
        $results = parent::toArray() + [
            'taskId' => $this->taskId,
            'status' => $this->status->value,
            'createdAt' => $this->createdAt,
            'lastUpdatedAt' => $this->lastUpdatedAt,
            'ttlMs' => $this->ttlMs,
        ];

        if(isset($this->statusMessage)) $results['statusMessage'] = $this->statusMessage;
        if(isset($this->pollIntervalMs)) $results['pollIntervalMs'] = $this->pollIntervalMs;

        return $results;
    }
}
