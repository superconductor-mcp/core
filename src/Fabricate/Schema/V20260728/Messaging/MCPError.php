<?php

namespace Fabricate\Schema\V20260728\Messaging;

use Fabricate\Contracts\Core\SuperconductorMCPException;
use Fabricate\Contracts\Schema\RPCErrorCode;
use Fabricate\Schema\Common\Messaging\MCPError as BaseError;
use Fabricate\Schema\V20260728\Concerns\MCPErrorCode;

readonly class MCPError extends BaseError
{
    public function __construct(
        int $code,
        string $message,
        mixed $data = null,
    ) {
        if(is_null(RPCErrorCode::tryFrom($code)) && is_null(MCPErrorCode::tryFrom($code)))
        {
            throw new SuperconductorMCPException(static::class.'- '.$code.' is not a known error code.');
        }

        parent::__construct($code, $message, $data);
    }
}
