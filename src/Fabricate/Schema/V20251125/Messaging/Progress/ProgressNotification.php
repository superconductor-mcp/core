<?php

namespace Fabricate\Schema\V20251125\Messaging\Progress;

use Fabricate\Contracts\Schema\Messaging\ClientNotification;
use Fabricate\Contracts\Schema\Messaging\Progress\ProgressNotification as ProgressContract;
use Fabricate\Contracts\Schema\Messaging\ServerNotification;
use Fabricate\Schema\Common\Messaging\RPC\JSONRPCNotification;
use Fabricate\Schema\V20251125\Messaging\Params\ProgressNotificationParams;

final readonly class ProgressNotification extends JSONRPCNotification implements ProgressContract, ClientNotification, ServerNotification
{
    public function __construct(
        ProgressNotificationParams $params
    ) {
        parent::__construct(
            "2.0",
            "notifications/progress",
            $params
        );
    }
}
