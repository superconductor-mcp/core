<?php

namespace Fabricate\Schema\V20260728\Messaging\Cancellation;

use Fabricate\Contracts\Schema\Messaging\Cancellation\CancelledNotification as CancelledContract;
use Fabricate\Contracts\Schema\Messaging\ClientNotification;
use Fabricate\Contracts\Schema\Messaging\ServerNotification;
use Fabricate\Schema\Common\Messaging\RPC\JSONRPCNotification;
use Fabricate\Schema\V20260728\Messaging\Params\CancelledNotificationParams;

final readonly class CancelledNotification extends JSONRPCNotification implements CancelledContract, ClientNotification, ServerNotification
{
    public function __construct(
        CancelledNotificationParams $params
    ) {
        parent::__construct(
            "2.0",
            "notifications/cancelled",
            $params
        );
    }
}
