<?php

namespace Fabricate\Contracts\Schema\Messaging\RPC;

use Voyager\Contracts\NutsAndBolts\Arrayable;

interface JSONRPCMessage extends Arrayable
{
    public function jsonrpc(): string;
    public function toJson(): string;
}
