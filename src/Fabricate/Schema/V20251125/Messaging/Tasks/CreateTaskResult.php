<?php

namespace Fabricate\Schema\V20251125\Messaging\Tasks;

use Fabricate\Contracts\Schema\Messaging\Tasks\CreateTaskResult as CreateTaskResultContract;
use Fabricate\Schema\V20251125\Messaging\Result;
use Fabricate\Schema\V20251125\Task;

final readonly class CreateTaskResult extends Result implements CreateTaskResultContract
{
    public function __construct(
        public Task $task,
        ?array $_meta = null,
    ) {
        parent::__construct($_meta);
    }

    public function toArray(): array
    {
        $results = parent::toArray();

        $results['task'] = $this->task->toArray();

        return $results;
    }
}
