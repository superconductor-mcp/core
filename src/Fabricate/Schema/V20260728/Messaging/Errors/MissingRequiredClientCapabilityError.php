<?php

namespace Fabricate\Schema\V20260728\Messaging\Errors;

use Fabricate\Contracts\Schema\RequestId;
use Fabricate\Schema\V20260728\Capabilities\ClientCapabilities;
use Fabricate\Schema\V20260728\Concerns\MCPErrorCode;
use Fabricate\Schema\V20260728\Messaging\MCPError;
use Fabricate\Schema\V20260728\Messaging\RPC\JSONRPCErrorResponse;

readonly class MissingRequiredClientCapabilityError extends JSONRPCErrorResponse
{
    public function __construct(
        ?RequestId $id,
        string $message,
        ClientCapabilities $required_capabilities,
    )
    {
        parent::__construct(
            "2.0",
            $id,
            new MCPError(
                MCPErrorCode::MISSING_REQUIRED_CLIENT_CAPABILITY->value,
                $message,
                ['requiredCapabilities' => $required_capabilities->toArray() ?: (object) []]
            )
        );
    }
}
