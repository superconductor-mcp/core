<?php

namespace Fabricate\Schema\V20251125\Messaging\Params;

use Fabricate\Contracts\Schema\ProgressToken;
use Fabricate\Schema\V20251125\TaskMetadata;

abstract readonly class TaskAugmentedRequestParams extends RequestParams
{
    public function __construct(
        public ?TaskMetadata $task = null,
        ?array $_meta = null,
        ?ProgressToken $progress_token = null
    ) {
        parent::__construct($_meta, $progress_token);
    }

    public function toArray(): array
    {
        $results = parent::toArray();

        if(isset($this->task)) $results['task'] = $this->task->toArray() ?: (object) [];

        return $results;
    }
}
