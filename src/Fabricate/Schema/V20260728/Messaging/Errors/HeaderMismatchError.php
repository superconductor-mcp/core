<?php

namespace Fabricate\Schema\V20260728\Messaging\Errors;

use Fabricate\Contracts\Schema\RequestId;
use Fabricate\Schema\V20260728\Concerns\MCPErrorCode;
use Fabricate\Schema\V20260728\Messaging\MCPError;
use Fabricate\Schema\V20260728\Messaging\RPC\JSONRPCErrorResponse;

readonly class HeaderMismatchError extends JSONRPCErrorResponse
{
    public function __construct(
        ?RequestId $id,
        string $message,
        mixed $data = null,
    )
    {
        parent::__construct(
            "2.0",
            $id,
            new MCPError(MCPErrorCode::HEADER_MISMATCH->value, $message, $data)
        );
    }
}
