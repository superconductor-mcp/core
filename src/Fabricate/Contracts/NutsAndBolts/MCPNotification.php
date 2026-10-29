<?php

namespace Fabricate\Contracts\NutsAndBolts;

// a JSON-RPC notification: a method and no id, so it is never answered
interface MCPNotification extends RPCMessage
{
    public string $method { get; }

    // the wire line a session sends for it
    public function toJson(): string;
}
