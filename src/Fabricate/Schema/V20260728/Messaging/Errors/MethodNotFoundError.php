<?php

namespace Fabricate\Schema\V20260728\Messaging\Errors;

use Fabricate\Contracts\Schema\RPCErrorCode;
use Fabricate\Schema\V20260728\Messaging\MCPError;

final readonly class MethodNotFoundError extends MCPError
{
    public function __construct(
        string $message,
        mixed $data = null,
    )
    {
        parent::__construct(
            RPCErrorCode::METHOD_NOT_FOUND->value,
            $message,
            $data
        );
    }
}
