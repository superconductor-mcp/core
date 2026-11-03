<?php

namespace Fabricate\Schema\V20260728\Messaging\Tasks;

use Fabricate\Contracts\Schema\Messaging\ServerNotification;
use Fabricate\Schema\Common\Messaging\RPC\JSONRPCNotification;
use Fabricate\Schema\V20260728\Messaging\Params\TaskStatusNotificationParams;

/**
 * notifications/tasks: a task's full current state, sent on a subscriptions/listen stream that named its id.
 */
final readonly class TaskStatusNotification extends JSONRPCNotification implements ServerNotification
{
    public function __construct(
        TaskStatusNotificationParams $params,
    ) {
        parent::__construct("2.0", "notifications/tasks", $params);
    }
}
