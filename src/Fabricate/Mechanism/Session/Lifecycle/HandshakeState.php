<?php

namespace Fabricate\Mechanism\Session\Lifecycle;

use Fabricate\NutsAndBolts\Messages\MCPRequest;
use Fabricate\NutsAndBolts\Messages\Ping\PingRequest;
use Fabricate\NutsAndBolts\Messages\Logging\SetLevelRequest;
use Fabricate\NutsAndBolts\Messages\Initialize\InitializeRequest;

// initialize answered, notifications/initialized not yet in: the spec allows pings and log level only
class HandshakeState extends LifecycleState
{
    public function canProcess(MCPRequest $request): bool
    {
        return $request instanceof InitializeRequest || $request instanceof PingRequest || $request instanceof SetLevelRequest;
    }

    public function nextState(): AvailableState
    {
        return new AvailableState();
    }
}
