<?php

namespace Fabricate\Schema\V20260728\Messaging\Resources;

use Fabricate\Contracts\Schema\Messaging\Resources\ResourceListChangedNotification as ListChangedContract;
use Fabricate\Contracts\Schema\Messaging\ServerNotification;
use Fabricate\Schema\Common\Messaging\RPC\JSONRPCNotification;
use Fabricate\Schema\V20260728\Messaging\Params\NotificationParams;

final readonly class ResourceListChangedNotification extends JSONRPCNotification implements ListChangedContract, ServerNotification
{
    public function __construct(
        ?NotificationParams $params = null,
    ) {
        parent::__construct("2.0", "notifications/resources/list_changed", $params);
    }
}
