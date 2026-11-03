<?php

namespace Fabricate\Contracts\Mechanism;

use Fabricate\Contracts\Schema\Messaging\RPC\JSONRPCMessage;

interface Entity
{
    public function receiveMessage(DecodedMessage $message): ?JSONRPCMessage;
}
