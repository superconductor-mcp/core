<?php

namespace Fabricate\Schema\V20260728\Messaging\Discover;

use Fabricate\Contracts\Schema\Messaging\Discover\DiscoverResultResponse as DiscoverContract;
use Fabricate\Contracts\Schema\RequestId;
use Fabricate\Schema\V20260728\Messaging\RPC\JSONRPCResultResponse;

final readonly class DiscoverResultResponse extends JSONRPCResultResponse implements DiscoverContract
{
    public function __construct(
        RequestId $id,
        DiscoverResult $result,
    ) {
        parent::__construct("2.0", $id, $result);
    }
}
