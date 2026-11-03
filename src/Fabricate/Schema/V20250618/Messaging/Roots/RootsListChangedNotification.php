<?php

namespace Fabricate\Schema\V20250618\Messaging\Roots;

use Fabricate\Contracts\Schema\Messaging\ClientNotification;
use Fabricate\Contracts\Schema\Messaging\Roots\RootsListChangedNotification as ListChangedContract;
use Fabricate\Schema\V20250618\Messaging\Notification;

final readonly class RootsListChangedNotification extends Notification implements ListChangedContract, ClientNotification
{
    public function __construct(
        ?array $_meta = null,
    ) {
        parent::__construct("notifications/roots/list_changed", null, $_meta);
    }
}
