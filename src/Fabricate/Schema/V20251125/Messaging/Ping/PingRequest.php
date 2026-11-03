<?php

namespace Fabricate\Schema\V20251125\Messaging\Ping;

use Fabricate\Contracts\Schema\Messaging\ClientRequest;
use Fabricate\Contracts\Schema\Messaging\Ping\PingRequest as PingContract;
use Fabricate\Contracts\Schema\Messaging\ServerRequest;
use Fabricate\Contracts\Schema\RequestId;
use Fabricate\Schema\Common\Messaging\RPC\JSONRPCRequest;
use Fabricate\Schema\V20251125\Messaging\Params\RequestParams;

final readonly class PingRequest extends JSONRPCRequest implements PingContract, ClientRequest, ServerRequest
{
    public function __construct(
        RequestId $id,
        ?RequestParams $params = null
    ) {
        parent::__construct("2.0", $id, 'ping', $params);
    }
}
