<?php

namespace Fabricate\Schema\V20250618\Messaging\Tools;

use Fabricate\Contracts\Schema\Messaging\ServerNotification;
use Fabricate\Contracts\Schema\Messaging\Tools\ToolListChangedNotification as ListChangedContract;
use Fabricate\Schema\V20250618\Messaging\Notification;

final readonly class ToolListChangedNotification extends Notification implements ListChangedContract, ServerNotification
{
    public function __construct(
        ?array $_meta = null,
    ) {
        parent::__construct("notifications/tools/list_changed", null, $_meta);
    }
}
