<?php

namespace Fabricate\Schema\V20241105\Messaging\RPC;

use Fabricate\Contracts\Schema\RequestId;
use Fabricate\Schema\Common\Messaging\Result;
use Fabricate\Schema\Common\Messaging\RPC\JSONRPCResponse as BaseJSONRPCResponse;

readonly class JSONRPCResponse extends BaseJSONRPCResponse
{
    public function __construct(
        string $jsonrpc,
        RequestId $id,
        Result $result,

    ) {
        parent::__construct($jsonrpc, $id, $result);
    }
}
