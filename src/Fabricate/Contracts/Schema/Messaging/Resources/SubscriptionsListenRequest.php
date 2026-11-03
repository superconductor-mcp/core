<?php

namespace Fabricate\Contracts\Schema\Messaging\Resources;

use Fabricate\Schema\V20260728\Capabilities\Resources\SubscriptionFilter;
use Fabricate\Contracts\Schema\Messaging\Request;

interface SubscriptionsListenRequest extends Request
{
    /** The notifications the client asks the subscription to carry. */
    public function notifications(): SubscriptionFilter;
}
