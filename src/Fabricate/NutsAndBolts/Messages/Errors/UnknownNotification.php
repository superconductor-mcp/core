<?php

declare(strict_types=1);

namespace Fabricate\NutsAndBolts\Messages\Errors;

// A notification nobody handles. It is mail so the sketch can log it, but it never goes back on the wire.
final readonly class UnknownNotification extends JsonRpcError
{
    public function __construct(string $method)
    {
        parent::__construct(null, -32601, 'Method not found', ['method' => $method]);
    }

    public function replies(): bool
    {
        return false;
    }
}
