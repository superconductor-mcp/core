<?php

namespace Fabricate\Schema\V20241105\Messaging\Initialize;

use Fabricate\Schema\V20241105\Messaging\Notification;
use Fabricate\Contracts\Schema\Messaging\ClientNotification;
use Fabricate\Contracts\Schema\Messaging\Initialize\InitializedNotification as InitializedContract;

final readonly class InitializedNotification extends Notification implements InitializedContract, ClientNotification
{
    public function __construct() {
        parent::__construct('notifications/initialized');
    }
}
