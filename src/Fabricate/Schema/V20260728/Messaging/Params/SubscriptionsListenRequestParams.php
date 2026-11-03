<?php

namespace Fabricate\Schema\V20260728\Messaging\Params;

use Fabricate\Schema\V20260728\Capabilities\Resources\SubscriptionFilter;
use Fabricate\Schema\V20260728\Messaging\MetaObjects\RequestMetaObject;

final readonly class SubscriptionsListenRequestParams extends RequestParams
{
    public function __construct(
        public SubscriptionFilter $notifications,
        RequestMetaObject $_meta,
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
