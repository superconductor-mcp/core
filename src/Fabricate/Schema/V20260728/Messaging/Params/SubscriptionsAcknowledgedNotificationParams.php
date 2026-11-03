<?php

namespace Fabricate\Schema\V20260728\Messaging\Params;

use Fabricate\Schema\V20260728\Capabilities\Resources\SubscriptionFilter;
use Fabricate\Schema\V20260728\Messaging\MetaObjects\NotificationMetaObject;

final readonly class SubscriptionsAcknowledgedNotificationParams extends NotificationParams
{
    public function __construct(
        public SubscriptionFilter $notifications,
        ?NotificationMetaObject $_meta = null,
    ) {
        parent::__construct($_meta);
    }

    public function toArray(): array
    {
        $results = parent::toArray();

        $results['notifications'] = $this->notifications->toArray() ?: (object) [];

        return $results;
    }
}
