<?php

namespace Fabricate\Schema\V20251125\Messaging\Roots;

use Fabricate\Contracts\Schema\Messaging\ClientNotification;
use Fabricate\Contracts\Schema\Messaging\Roots\RootsListChangedNotification as ListChangedContract;
use Fabricate\Schema\Common\Messaging\RPC\JSONRPCNotification;
use Fabricate\Schema\V20251125\Messaging\Params\NotificationParams;

final readonly class RootsListChangedNotification extends JSONRPCNotification implements ListChangedContract, ClientNotification
{
    public function __construct(
        ?NotificationParams $params = null,
    ) {
        parent::__construct("2.0", "notifications/roots/list_changed", $params);
    }
}
