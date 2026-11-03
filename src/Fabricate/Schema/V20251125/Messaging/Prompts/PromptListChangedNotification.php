<?php

namespace Fabricate\Schema\V20251125\Messaging\Prompts;

use Fabricate\Contracts\Schema\Messaging\Prompts\PromptListChangedNotification as ListChangedContract;
use Fabricate\Contracts\Schema\Messaging\ServerNotification;
use Fabricate\Schema\Common\Messaging\RPC\JSONRPCNotification;
use Fabricate\Schema\V20251125\Messaging\Params\NotificationParams;

final readonly class PromptListChangedNotification extends JSONRPCNotification implements ListChangedContract, ServerNotification
{
    public function __construct(
        ?NotificationParams $params = null,
    ) {
        parent::__construct("2.0", "notifications/prompts/list_changed", $params);
    }
}
