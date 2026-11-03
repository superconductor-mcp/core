<?php

namespace Fabricate\Schema\V20260728\Messaging\Tools;

use Fabricate\Contracts\Schema\Messaging\ServerNotification;
use Fabricate\Contracts\Schema\Messaging\Tools\ToolListChangedNotification as ListChangedContract;
use Fabricate\Schema\Common\Messaging\RPC\JSONRPCNotification;
use Fabricate\Schema\V20260728\Messaging\Params\NotificationParams;

final readonly class ToolListChangedNotification extends JSONRPCNotification implements ListChangedContract, ServerNotification
{
    public function __construct(
        ?NotificationParams $params = null,
    ) {
        parent::__construct("2.0", "notifications/tools/list_changed", $params);
    }
}
