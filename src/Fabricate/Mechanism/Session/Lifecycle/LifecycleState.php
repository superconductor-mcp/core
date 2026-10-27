<?php

namespace Fabricate\Mechanism\Session\Lifecycle;

use Fabricate\NutsAndBolts\Messages\MCPRequest;

// what a session's client may ask for at this point of the lifecycle; the same three states on every transport
abstract class LifecycleState
{
    abstract public function canProcess(MCPRequest $request): bool;

    abstract public function nextState(): LifecycleState;
}
