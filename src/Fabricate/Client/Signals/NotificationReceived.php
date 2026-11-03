<?php

namespace Fabricate\Client\Signals;

use Fabricate\Contracts\Schema\Messaging\Notification;

/**
 * Fired for every notification a server sends the client. The application decides what, if anything, to do.
 */
final readonly class NotificationReceived
{
    public function __construct(
        public string $session_id,
        public Notification $notification,
    ) {}
}
