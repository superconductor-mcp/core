<?php

namespace Fabricate\Schema\V20241105\Messaging;

use Fabricate\Contracts\Core\SuperconductorMCPException;
use Fabricate\Contracts\Schema\ResourceErrorCode;
use Fabricate\Contracts\Schema\RPCErrorCode;
use Fabricate\Contracts\Schema\SamplingErrorCode;
use Fabricate\Schema\Common\Messaging\MCPError as BaseError;

readonly class MCPError extends BaseError
{
    public function __construct(
        int $code,
        string $message,
        mixed $data = null,
    ) {
        if(is_null(RPCErrorCode::tryFrom($code)) && is_null(SamplingErrorCode::tryFrom($code)) && is_null(ResourceErrorCode::tryFrom($code)))
        {
            throw new SuperconductorMCPException(static::class.'- '.$code.' is not a known error code.');
        }

        parent::__construct($code, $message, $data);
    }
}
