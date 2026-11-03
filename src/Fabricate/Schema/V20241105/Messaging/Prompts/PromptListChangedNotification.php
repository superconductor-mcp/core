<?php

namespace Fabricate\Schema\V20241105\Messaging\Prompts;

use Fabricate\Contracts\Schema\Messaging\Prompts\PromptListChangedNotification as ListChangedContract;
use Fabricate\Contracts\Schema\Messaging\ServerNotification;
use Fabricate\Schema\V20241105\Messaging\Notification;

final readonly class PromptListChangedNotification extends Notification implements ListChangedContract, ServerNotification
{
    public function __construct(
        ?array $_meta = null,
    ) {
        parent::__construct("notifications/prompts/list_changed", null, $_meta);
    }
}
