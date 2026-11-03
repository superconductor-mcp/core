<?php

namespace Fabricate\Contracts\Core;

use RuntimeException;
use Throwable;

class SuperconductorMCPException extends RuntimeException
{
    /**
     * @param mixed $data What the JSON-RPC error carries in its data member, if anything.
     */
    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null, public readonly mixed $data = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
