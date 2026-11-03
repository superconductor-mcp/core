<?php

namespace Fabricate\Schema\V20260728\Messaging\Params;

use Fabricate\Schema\V20260728\Messaging\MetaObjects\RequestMetaObject;

/**
 * The params of tasks/get and tasks/cancel: the task they are about.
 */
readonly class TaskRequestParams extends RequestParams
{
    public function __construct(
        public string $taskId,
        RequestMetaObject $_meta,
    ) {
        parent::__construct($_meta);
    }

    public function toArray(): array
    {
        return parent::toArray() + ['taskId' => $this->taskId];
    }
}
