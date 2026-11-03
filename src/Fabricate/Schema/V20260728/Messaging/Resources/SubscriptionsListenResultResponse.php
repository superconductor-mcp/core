<?php

namespace Fabricate\Schema\V20260728\Messaging\Resources;

use Fabricate\Contracts\Schema\RequestId;
use Fabricate\Schema\V20260728\Messaging\RPC\JSONRPCResultResponse;

final readonly class SubscriptionsListenResultResponse extends JSONRPCResultResponse
{
    public function __construct(
        RequestId $id,
        SubscriptionsListenResult $result,
    ) {
        parent::__construct("2.0", $id, $result);
    }
}
