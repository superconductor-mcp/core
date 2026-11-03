<?php

namespace Fabricate\Mechanism\Hydration;

use Fabricate\Contracts\Mechanism\MCPMechanismException;
use Fabricate\Contracts\Schema\RPCErrorCode;

/** A message that does not fit the schema class being built. Caught by the hydrator and answered with an MCPError. */
class HydrationException extends MCPMechanismException
{
    public function __construct(
        string $message,
        public readonly RPCErrorCode $error_code = RPCErrorCode::INVALID_PARAMS,
    ) {
        parent::__construct($message, $error_code->value);
    }
}
