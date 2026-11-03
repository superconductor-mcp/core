<?php

namespace Fabricate\Contracts\Schema\Messaging\RPC;

use Fabricate\Contracts\Schema\Messaging\Request;

/** A JSON-RPC request wrapped around a flat message, as 2024-11-05 to 2025-06-18 define them. */
interface JSONRPCEnvelope extends JSONRPCRequest
{
    public Request $request { get; }
}
