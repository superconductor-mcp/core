<?php

namespace Fabricate\Schema\V20260728\Messaging\RPC;

use Fabricate\Contracts\Schema\RequestId;
use Fabricate\Schema\V20260728\Messaging\MCPError;

readonly class JSONRPCErrorResponse extends JSONRPCResponse
{
    public function __construct(
        string $jsonrpc,
        ?RequestId $id,
        MCPError $error,

    ) {
        parent::__construct($jsonrpc, $id, null, $error);
    }
}
