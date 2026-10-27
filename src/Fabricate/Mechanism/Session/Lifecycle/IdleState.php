<?php

namespace Fabricate\Mechanism\Session\Lifecycle;

use Fabricate\NutsAndBolts\Messages\MCPRequest;
use Fabricate\NutsAndBolts\Messages\Initialize\InitializeRequest;

// no client yet: initialize is the only request that makes sense, even a ping is refused
class IdleState extends LifecycleState
{
    public function canProcess(MCPRequest $request): bool
    {
        return $request instanceof InitializeRequest;
    }

    public function nextState(): HandshakeState
    {
        return new HandshakeState();
    }
}
