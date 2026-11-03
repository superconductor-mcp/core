<?php

namespace Fabricate\Schema\V20260728\Messaging\Discover;

use Fabricate\Contracts\Schema\Messaging\ClientRequest;
use Fabricate\Contracts\Schema\Messaging\Discover\DiscoverRequest as DiscoverContract;
use Fabricate\Contracts\Schema\RequestId;
use Fabricate\Schema\Common\Messaging\RPC\JSONRPCRequest;
use Fabricate\Schema\V20260728\Messaging\Params\RequestParams;

final readonly class DiscoverRequest extends JSONRPCRequest implements DiscoverContract, ClientRequest
{
    public function __construct(
        RequestId $id,
        RequestParams $params
    ) {
        parent::__construct(
            "2.0",
            $id,
            "server/discover",
            $params
        );
    }
}
