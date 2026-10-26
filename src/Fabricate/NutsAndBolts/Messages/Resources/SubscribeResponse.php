<?php

namespace Fabricate\NutsAndBolts\Messages\Resources;

use Fabricate\NutsAndBolts\Messages\MCPResponse;

final readonly class SubscribeResponse extends MCPResponse
{
    public function __construct(
        string|int $id,
    ) {
        parent::__construct($id, []);
    }

    public function toJson(): string
    {
        return json_encode([
            'jsonrpc' => '2.0',
            'id' => $this->id,
            // a subscribe is acknowledged with an empty result object; a PHP [] would go out as a JSON array
            'result' => new \stdClass(),
        ]);
    }
}
