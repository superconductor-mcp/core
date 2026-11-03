<?php

namespace Fabricate\Schema\V20260728\Messaging\Resources;

use Fabricate\Contracts\Schema\RequestId;
use Fabricate\Schema\V20260728\Messaging\RPC\JSONRPCResultResponse;

final readonly class ListResourceTemplatesResultResponse extends JSONRPCResultResponse
{
    public function __construct(
        RequestId $id,
        ListResourceTemplatesResult $result,
    ) {
        parent::__construct("2.0", $id, $result);
    }
}
