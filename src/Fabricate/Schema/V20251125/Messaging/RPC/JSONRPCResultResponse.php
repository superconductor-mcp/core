<?php

namespace Fabricate\Schema\V20251125\Messaging\RPC;

use Fabricate\Contracts\Schema\RequestId;
use Fabricate\Schema\Common\Messaging\Result;

readonly class JSONRPCResultResponse extends JSONRPCResponse
{
    public function __construct(
        string $jsonrpc,
        RequestId $id,
        Result $result,

    ) {
        parent::__construct($jsonrpc, $id, $result);
    }
}
