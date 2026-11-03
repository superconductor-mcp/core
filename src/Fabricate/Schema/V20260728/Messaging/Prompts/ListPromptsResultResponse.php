<?php

namespace Fabricate\Schema\V20260728\Messaging\Prompts;

use Fabricate\Contracts\Schema\RequestId;
use Fabricate\Schema\V20260728\Messaging\RPC\JSONRPCResultResponse;

final readonly class ListPromptsResultResponse extends JSONRPCResultResponse
{
    public function __construct(
        RequestId $id,
        ListPromptsResult $result,
    ) {
        parent::__construct("2.0", $id, $result);
    }
}
