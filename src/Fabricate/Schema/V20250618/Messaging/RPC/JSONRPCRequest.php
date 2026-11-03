<?php

namespace Fabricate\Schema\V20250618\Messaging\RPC;

use Fabricate\Contracts\Schema\Messaging\RPC\JSONRPCEnvelope;
use Fabricate\Contracts\Schema\RequestId;
use Fabricate\Schema\Common\Messaging\RPC\JSONRPCRequest as BaseJSONRPCRequest;
use Fabricate\Schema\V20250618\Messaging\Request;

readonly class JSONRPCRequest extends BaseJSONRPCRequest implements JSONRPCEnvelope
{
    public function __construct(
        string $jsonrpc,
        RequestId $id,
        public Request $request,
    ) {
        parent::__construct($jsonrpc, $id, $request->method, $request->params);
    }
}
