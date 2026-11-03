<?php

namespace Fabricate\Schema\V20250326\Messaging\RPC;

use Fabricate\Schema\Common\Messaging\RPC\JSONRPCNotification as BaseJSONRPCNotification;
use Fabricate\Schema\V20250326\Messaging\Notification;

readonly class JSONRPCNotification extends BaseJSONRPCNotification
{
    public function __construct(
        string $jsonrpc,
        Notification $notification,
    ) {
        parent::__construct($jsonrpc, $notification->method, $notification->params);
    }
}
