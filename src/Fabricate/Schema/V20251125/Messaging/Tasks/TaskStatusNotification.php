<?php

namespace Fabricate\Schema\V20251125\Messaging\Tasks;

use Fabricate\Contracts\Schema\Messaging\ClientNotification;
use Fabricate\Contracts\Schema\Messaging\ServerNotification;
use Fabricate\Schema\Common\Messaging\RPC\JSONRPCNotification;
use Fabricate\Schema\V20251125\Messaging\Params\TaskStatusNotificationParams;

final readonly class TaskStatusNotification extends JSONRPCNotification implements ClientNotification, ServerNotification
{
    public function __construct(
        TaskStatusNotificationParams $params,
    ) {
        parent::__construct("2.0", 'notifications/tasks/status', $params);
    }
}
