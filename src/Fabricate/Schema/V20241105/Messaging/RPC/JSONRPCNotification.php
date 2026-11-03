<?php

namespace Fabricate\Schema\V20241105\Messaging\RPC;

use Fabricate\Schema\Common\Messaging\RPC\JSONRPCNotification as BaseJSONRPCNotification;
use Fabricate\Schema\V20241105\Messaging\Notification;

readonly class JSONRPCNotification extends BaseJSONRPCNotification
{
    public function __construct(
        string $jsonrpc,
        Notification $notification,
    ) {
        parent::__construct($jsonrpc, $notification->method, $notification->params);
    }
}
