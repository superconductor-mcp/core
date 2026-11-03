<?php

namespace Fabricate\Schema\V20260728\Messaging\Errors;

use Fabricate\Contracts\Schema\RPCErrorCode;
use Fabricate\Schema\V20260728\Messaging\MCPError;

final readonly class ParseError extends MCPError
{
    public function __construct(
        string $message,
        mixed $data = null,
    )
    {
        parent::__construct(
            RPCErrorCode::PARSE_ERROR->value,
            $message,
            $data
        );
    }
}
