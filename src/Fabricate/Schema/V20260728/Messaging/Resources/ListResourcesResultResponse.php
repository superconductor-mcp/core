<?php

namespace Fabricate\Schema\V20260728\Messaging\Resources;

use Fabricate\Contracts\Schema\RequestId;
use Fabricate\Schema\V20260728\Messaging\RPC\JSONRPCResultResponse;

final readonly class ListResourcesResultResponse extends JSONRPCResultResponse
{
    public function __construct(
        RequestId $id,
        ListResourcesResult $result,
    ) {
        parent::__construct("2.0", $id, $result);
    }
}
