<?php

declare(strict_types=1);

namespace Fabricate\NutsAndBolts\Messages\Errors;

use Fabricate\Contracts\NutsAndBolts\MCPError;
use Fabricate\NutsAndBolts\Messages\MCPResponse;

abstract readonly class JsonRpcError extends MCPResponse implements MCPError
{
    public function __construct(
        string|int|null $id,
        public int $code,
        public string $message,
        public mixed $data = null,
    ) {
        parent::__construct(
            $id,
            null,
            $data === null
                ? ['code' => $code, 'message' => $message]
                : ['code' => $code, 'message' => $message, 'data' => $data],
        );
    }

    public function toJson(): string
    {
        return json_encode([
            'jsonrpc' => '2.0',
            'id' => $this->id,
            'error' => $this->error,
        ]);
    }

    // JSON-RPC answers every failed request, but never a notification, not even with an error.
    public function replies(): bool
    {
        return true;
    }
}
