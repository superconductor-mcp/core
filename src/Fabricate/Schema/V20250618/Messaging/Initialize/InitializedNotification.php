<?php

namespace Fabricate\Schema\V20250618\Messaging\Initialize;

use Fabricate\Schema\V20250618\Messaging\Notification;
use Fabricate\Contracts\Schema\Messaging\ClientNotification;
use Fabricate\Contracts\Schema\Messaging\Initialize\InitializedNotification as InitializedContract;

final readonly class InitializedNotification extends Notification implements InitializedContract, ClientNotification
{
    public function __construct() {
        parent::__construct('notifications/initialized');
    }
}
