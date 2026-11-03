<?php

namespace Fabricate\Schema\V20260728\Messaging\Logging;

use Fabricate\Contracts\Schema\Messaging\Logging\LoggingMessageNotification as LoggingContract;
use Fabricate\Contracts\Schema\Messaging\ServerNotification;
use Fabricate\Schema\Common\Messaging\RPC\JSONRPCNotification;
use Fabricate\Schema\V20260728\Messaging\Params\LoggingMessageNotificationParams;

final readonly class LoggingMessageNotification extends JSONRPCNotification implements LoggingContract, ServerNotification
{
    public function __construct(
        LoggingMessageNotificationParams $params,
    ) {
        parent::__construct("2.0", 'notifications/message', $params);
    }
}
