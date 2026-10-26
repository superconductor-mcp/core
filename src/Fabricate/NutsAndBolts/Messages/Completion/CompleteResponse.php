<?php

namespace Fabricate\NutsAndBolts\Messages\Completion;

use Fabricate\NutsAndBolts\CompletionResult;
use Fabricate\NutsAndBolts\Messages\MCPResponse;

final readonly class CompleteResponse extends MCPResponse
{
    public function __construct(
        string|int $id,
        public CompletionResult $completion = new CompletionResult(),
    ) {
        parent::__construct($id, ['completion' => $completion->toArray()]);
    }

    public function toJson(): string
    {
        return json_encode([
            'jsonrpc' => '2.0',
            'id' => $this->id,
            // values is always a JSON array, even with no suggestions
            'result' => ['completion' => $this->completion->toArray()],
        ]);
    }
}
