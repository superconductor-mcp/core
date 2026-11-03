<?php

namespace Fabricate\Schema\V20260728\Messaging\RPC;

use Fabricate\Contracts\Schema\RequestId;
use Fabricate\Schema\V20260728\Messaging\Result;

readonly class JSONRPCResultResponse extends JSONRPCResponse
{
    public function __construct(
        string $jsonrpc,
        RequestId $id,
        Result $result,

    ) {
        parent::__construct($jsonrpc, $id, $result);
    }

    public function jsonrpc(): string
    {
        return $this->jsonrpc;
    }
}
