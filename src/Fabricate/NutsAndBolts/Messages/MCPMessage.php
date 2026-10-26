<?php

namespace Fabricate\NutsAndBolts\Messages;

use Fabricate\Contracts\NutsAndBolts\RPCMessage;

abstract readonly class MCPMessage implements RPCMessage
{
    public function __construct() {}
}