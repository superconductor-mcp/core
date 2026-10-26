<?php

declare(strict_types=1);

namespace Fabricate\NutsAndBolts\Messages\Errors;

// JSON, but not a JSON-RPC message. The id is echoed only when it was readable and valid.
final readonly class InvalidRequest extends JsonRpcError
{
    public function __construct(string|int|null $id, string $detail)
    {
        parent::__construct($id, -32600, 'Invalid Request', $detail);
    }
}
