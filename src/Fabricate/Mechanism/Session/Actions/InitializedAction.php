<?php

namespace Fabricate\Mechanism\Session\Actions;

use Fabricate\Mechanism\Session\SessionBag;
use Fabricate\NutsAndBolts\Messages\Initialize\InitializedNotification;

// notifications/initialized: the handshake is complete
class InitializedAction
{
    public function handle(SessionBag $bag, ?InitializedNotification $notification = null): void
    {
        $bag->state = $bag->state->nextState();
    }
}
