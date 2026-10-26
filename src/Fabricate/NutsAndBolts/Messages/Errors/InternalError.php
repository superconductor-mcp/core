<?php

declare(strict_types=1);

namespace Fabricate\NutsAndBolts\Messages\Errors;

// the server failed while handling a valid request; the reason goes in data
final readonly class InternalError extends JsonRpcError
{
    public function __construct(string|int $id, string $detail)
    {
        parent::__construct($id, -32603, 'Internal error', $detail);
    }
}
