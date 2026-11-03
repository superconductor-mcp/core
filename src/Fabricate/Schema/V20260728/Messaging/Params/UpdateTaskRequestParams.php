<?php

namespace Fabricate\Schema\V20260728\Messaging\Params;

use Fabricate\Schema\V20260728\Messaging\Inputs\InputResponses;
use Fabricate\Schema\V20260728\Messaging\MetaObjects\RequestMetaObject;

/**
 * The params of tasks/update: the task, and responses to its outstanding inputRequests.
 */
readonly class UpdateTaskRequestParams extends TaskRequestParams
{
    public function __construct(
        string $taskId,
        public InputResponses $inputResponses,
        RequestMetaObject $_meta,
    ) {
        parent::__construct($taskId, $_meta);
    }

    public function toArray(): array
    {
        return parent::toArray() + ['inputResponses' => $this->inputResponses->toArray() ?: (object) []];
    }
}
