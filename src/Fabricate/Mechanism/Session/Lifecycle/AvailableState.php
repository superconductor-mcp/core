<?php

namespace Fabricate\Mechanism\Session\Lifecycle;

use Fabricate\NutsAndBolts\Messages\MCPRequest;
use Fabricate\NutsAndBolts\Messages\Initialize\InitializeRequest;

// the session is up: everything but a second initialize
class AvailableState extends LifecycleState
{
    public function canProcess(MCPRequest $request): bool
    {
        return ! $request instanceof InitializeRequest;
    }

    public function nextState(): AvailableState
    {
        return $this;
    }
}
