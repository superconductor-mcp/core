<?php

namespace Fabricate\Schema\V20251125\Messaging\Params;

use Fabricate\Schema\V20251125\Task;

final readonly class TaskStatusNotificationParams extends NotificationParams
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
