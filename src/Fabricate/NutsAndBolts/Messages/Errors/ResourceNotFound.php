<?php

declare(strict_types=1);

namespace Fabricate\NutsAndBolts\Messages\Errors;

// resources/read named a uri no resource or template on the server matches; 2024-11-05 fixes the code at -32002
final readonly class ResourceNotFound extends JsonRpcError
{
    public function __construct(string|int $id, string $uri)
    {
        parent::__construct($id, -32002, 'Resource not found', ['uri' => $uri]);
    }
}
