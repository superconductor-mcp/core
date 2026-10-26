<?php

declare(strict_types=1);

namespace Fabricate\NutsAndBolts\Messages\Errors;

// The line is not JSON, so no id can be read: JSON-RPC replies with a null id.
final readonly class ParseError extends JsonRpcError
{
    public function __construct(string $detail)
    {
        parent::__construct(null, -32700, 'Parse error', $detail);
    }
}
