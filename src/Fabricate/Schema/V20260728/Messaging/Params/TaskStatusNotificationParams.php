<?php

namespace Fabricate\Schema\V20260728\Messaging\Params;

use Fabricate\Schema\V20260728\Messaging\MetaObjects\NotificationMetaObject;
use Fabricate\Schema\V20260728\Messaging\Tasks\DetailedTask;

readonly class TaskStatusNotificationParams extends NotificationParams
{
    public function __construct(
        public DetailedTask $task,
        ?NotificationMetaObject $_meta = null,
    ) {
        parent::__construct($_meta);
    }

    public function toArray(): array
    {
        return parent::toArray() + $this->task->toArray();
    }
}
