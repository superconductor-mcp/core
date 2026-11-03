<?php

namespace Fabricate\Schema\V20250326\Messaging\Resources;

use Fabricate\Contracts\Schema\Messaging\Resources\ResourceListChangedNotification as ListChangedContract;
use Fabricate\Contracts\Schema\Messaging\ServerNotification;
use Fabricate\Schema\V20250326\Messaging\Notification;

final readonly class ResourceListChangedNotification extends Notification implements ListChangedContract, ServerNotification
{
    public function __construct(
        ?array $_meta = null,
    ) {
        parent::__construct("notifications/resources/list_changed", null, $_meta);
    }
}
