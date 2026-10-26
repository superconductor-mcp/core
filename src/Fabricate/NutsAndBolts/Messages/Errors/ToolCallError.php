<?php

declare(strict_types=1);

namespace Fabricate\NutsAndBolts\Messages\Errors;

// tools/call named a tool the server is not offering. 2024-11-05 treats this as a protocol error with -32602.
final readonly class ToolCallError extends JsonRpcError
{
    public function __construct(string|int $id, string $tool)
    {
        parent::__construct($id, -32602, "Unknown tool: {$tool}", ['tool' => $tool]);
    }
}
