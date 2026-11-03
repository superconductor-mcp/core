<?php

namespace Fabricate\Schema\V20251125\Messaging\Resources;

use Fabricate\Contracts\Schema\Messaging\Resources\ResourceUpdatedNotification as UpdatedContract;
use Fabricate\Contracts\Schema\Messaging\ServerNotification;
use Fabricate\Schema\Common\Messaging\RPC\JSONRPCNotification;
use Fabricate\Schema\V20251125\Messaging\Params\ResourceUpdatedNotificationParams;

final readonly class ResourceUpdatedNotification extends JSONRPCNotification implements UpdatedContract, ServerNotification
{
    public function __construct(
        ResourceUpdatedNotificationParams $params,
    ){
        parent::__construct("2.0", 'notifications/resources/updated', $params);
    }
}
