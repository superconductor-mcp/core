<?php

namespace Fabricate\Schema\V20260728\Messaging\Errors;

use Fabricate\Contracts\Schema\RequestId;
use Fabricate\Schema\V20260728\Concerns\MCPErrorCode;
use Fabricate\Schema\V20260728\Messaging\MCPError;
use Fabricate\Schema\V20260728\Messaging\RPC\JSONRPCErrorResponse;

readonly class UnsupportedProtocolVersionError extends JSONRPCErrorResponse
{
    /**
     * @param string[] $supported
     */
    public function __construct(
        ?RequestId $id,
        string $message,
        array $supported,
        string $requested,
    )
    {
        parent::__construct(
            "2.0",
            $id,
            new MCPError(
                MCPErrorCode::UNSUPPORTED_PROTOCOL_VERSION->value,
                $message,
                ['supported' => $supported, 'requested' => $requested]
            )
        );
    }
}
