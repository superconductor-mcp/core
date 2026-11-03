<?php

namespace Fabricate\Contracts\Mechanism;

use Fabricate\Contracts\Schema\RequestId;

/** A single inbound JSON-RPC message after its envelope has been read and checked. */
interface DecodedMessage
{
    /** The message's JSON-RPC id; null for a notification, or an error or invalid message whose id could not be read. */
    public function id(): ?RequestId;
}
