<?php

namespace Fabricate\Schema\V20260728\Messaging\Prompts;

use Fabricate\Contracts\Schema\RequestId;
use Fabricate\Schema\V20260728\Messaging\Inputs\InputRequiredResult;
use Fabricate\Schema\V20260728\Messaging\RPC\JSONRPCResultResponse;

final readonly class GetPromptResultResponse extends JSONRPCResultResponse
{
    public function __construct(
        RequestId $id,
        GetPromptResult|InputRequiredResult $result,
    ) {
        parent::__construct("2.0", $id, $result);
    }
}
