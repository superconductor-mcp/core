<?php

namespace Fabricate\Mechanism\Hydration;

use Fabricate\Contracts\Schema\RPCErrorCode;

/**
 * A message that names, or structurally belongs to, another protocol version. Ranked below every
 * shape failure when choosing which version's error to report, since the message was never meant for this one.
 */
final class NotThisVersion extends HydrationException
{
    public function __construct(string $message, RPCErrorCode $error_code = RPCErrorCode::INVALID_PARAMS)
    {
        parent::__construct($message, $error_code);
    }
}
