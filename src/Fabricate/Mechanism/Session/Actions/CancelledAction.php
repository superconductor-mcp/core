<?php

namespace Fabricate\Mechanism\Session\Actions;

use Fabricate\Mechanism\Session\SessionBag;
use Fabricate\NutsAndBolts\Messages\Cancellation\CancelledNotification;

// notifications/cancelled: a request still running has its response suppressed; anything else is ignored
class CancelledAction
{
    public function handle(SessionBag $bag, CancelledNotification $notification): void
    {
        // both sides should log cancellation reasons
        app('log')->info('mcp.session request cancelled by client', [
            'request_id' => $notification->request_id,
            'reason' => $notification->reason,
            'in_flight' => isset($bag->in_flight[$notification->request_id]),
        ]);

        if (isset($bag->in_flight[$notification->request_id])) {
            $bag->cancelled[$notification->request_id] = true;
        }
    }
}
