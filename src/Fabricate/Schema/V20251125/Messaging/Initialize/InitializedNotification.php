<?php

namespace Fabricate\Schema\V20251125\Messaging\Initialize;

use Fabricate\Contracts\Schema\Messaging\ClientNotification;
use Fabricate\Contracts\Schema\Messaging\Initialize\InitializedNotification as InitializedContract;
use Fabricate\Schema\Common\Messaging\RPC\JSONRPCNotification;
use Fabricate\Schema\V20251125\Messaging\Params\NotificationParams;

final readonly class InitializedNotification extends JSONRPCNotification implements InitializedContract, ClientNotification
{
    public function __construct(
        ?NotificationParams $params = null,
    ) {
        parent::__construct(
            "2.0",
            'notifications/initialized',
            $params
        );
    }
}
