<?php

declare(strict_types=1);

namespace Fabricate\NutsAndBolts\Messages\Errors;

final readonly class MethodNotFound extends JsonRpcError
{
    public function __construct(string|int $id, string $method)
    {
        parent::__construct($id, -32601, 'Method not found', ['method' => $method]);
    }
}
