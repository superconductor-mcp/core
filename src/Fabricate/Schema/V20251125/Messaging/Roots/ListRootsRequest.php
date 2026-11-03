<?php

namespace Fabricate\Schema\V20251125\Messaging\Roots;

use Fabricate\Contracts\Schema\Messaging\Roots\ListRootsRequest as ListRootsContract;
use Fabricate\Contracts\Schema\Messaging\ServerRequest;
use Fabricate\Contracts\Schema\RequestId;
use Fabricate\Schema\Common\Messaging\RPC\JSONRPCRequest;
use Fabricate\Schema\V20251125\Messaging\Params\RequestParams;

final readonly class ListRootsRequest extends JSONRPCRequest implements ListRootsContract, ServerRequest
{
    public function __construct(
        RequestId $id,
        ?RequestParams $params = null,
    ) {
        parent::__construct("2.0", $id, 'roots/list', $params);
    }
}
