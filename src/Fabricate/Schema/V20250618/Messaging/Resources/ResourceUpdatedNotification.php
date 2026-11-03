<?php

namespace Fabricate\Schema\V20250618\Messaging\Resources;

use Fabricate\Contracts\Schema\Messaging\Resources\ResourceUpdatedNotification as UpdatedContract;
use Fabricate\Contracts\Schema\Messaging\ServerNotification;
use Fabricate\Schema\V20250618\Messaging\Notification;

final readonly class ResourceUpdatedNotification extends Notification implements UpdatedContract, ServerNotification
{
    public function __construct(
        string $uri,
        ?array $_meta = null,
    ){
        parent::__construct('notifications/resources/updated', ['uri' => $uri], $_meta);
    }
}
