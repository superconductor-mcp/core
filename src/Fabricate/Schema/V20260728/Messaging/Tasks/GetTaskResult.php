<?php

namespace Fabricate\Schema\V20260728\Messaging\Tasks;

use Fabricate\Contracts\Schema\Messaging\ServerResult;
use Fabricate\Contracts\Schema\Messaging\Tasks\GetTaskResult as GetTaskContract;
use Fabricate\Schema\V20260728\Concerns\ResultTypeEnum;
use Fabricate\Schema\V20260728\Messaging\MetaObjects\ResultMetaObject;
use Fabricate\Schema\V20260728\Messaging\Result;

/**
 * The answer to tasks/get: the task's current state, with the fields its status carries.
 */
final readonly class GetTaskResult extends Result implements GetTaskContract, ServerResult
{
    public function __construct(
        public DetailedTask $task,
        ?ResultMetaObject $_meta = null,
    ) {
        parent::__construct(ResultTypeEnum::COMPLETE, $_meta);
    }

    public function toArray(): array
    {
        return parent::toArray() + $this->task->toArray();
    }
}
