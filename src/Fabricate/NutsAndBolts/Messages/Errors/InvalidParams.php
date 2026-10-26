<?php

declare(strict_types=1);

namespace Fabricate\NutsAndBolts\Messages\Errors;

// A known method whose params veered off the expected shape. The id was already proven, so it is echoed.
final readonly class InvalidParams extends JsonRpcError
{
    public function __construct(string|int $id, string $detail)
    {
        parent::__construct($id, -32602, 'Invalid params', $detail);
    }
}
