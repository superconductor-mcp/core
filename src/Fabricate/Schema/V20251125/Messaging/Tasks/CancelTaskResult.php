<?php

namespace Fabricate\Schema\V20251125\Messaging\Tasks;

use Fabricate\Contracts\Schema\Messaging\Tasks\CancelTaskResult as CancelTaskResultContract;
use Fabricate\Contracts\Schema\Messaging\ClientResult;
use Fabricate\Contracts\Schema\Messaging\ServerResult;
use Fabricate\Schema\V20251125\Messaging\Result;
use Fabricate\Schema\V20251125\Task;

final readonly class CancelTaskResult extends Result implements CancelTaskResultContract, ClientResult, ServerResult
{
    public function __construct(
        public Task $task,
        ?array $_meta = null,
    ) {
        parent::__construct($_meta);
    }

    public function toArray(): array
    {
        return parent::toArray() + $this->task->toArray();
    }
}
