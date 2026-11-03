<?php

namespace Fabricate\Contracts\Schema\Messaging\RPC;


interface JSONRPCResponse extends JSONRPCMessage
{
    public function result(): mixed;
}
