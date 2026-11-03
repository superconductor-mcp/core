<?php

namespace Fabricate\Schema\V20260728\Messaging\Resources;

use Fabricate\Contracts\Schema\Messaging\ServerNotification;
use Fabricate\Schema\Common\Messaging\RPC\JSONRPCNotification;
use Fabricate\Schema\V20260728\Messaging\Params\SubscriptionsAcknowledgedNotificationParams;

final readonly class SubscriptionsAcknowledgedNotification extends JSONRPCNotification implements ServerNotification
{
    public function __construct(
        SubscriptionsAcknowledgedNotificationParams $params,
    ) {
        parent::__construct("2.0", 'notifications/subscriptions/acknowledged', $params);
    }
}
