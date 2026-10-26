<?php

namespace Fabricate\NutsAndBolts\Messages\Ping;

use Fabricate\NutsAndBolts\Messages\MCPResponse;
use Fabricate\NutsAndBolts\Messages\Responses\ReceivedResponse;

final readonly class PingResponse extends MCPResponse
{
    public function __construct(
        string|int $id,
    ) {
        parent::__construct($id, []);
    }

    public function toJson(): string
    {
        $payload = [
            'jsonrpc' => '2.0',
            'id' => $this->id,
            // ping answers with an empty result object; a PHP [] would go out as a JSON array
            'result' => new \stdClass(),
        ];

        return json_encode($payload);
    }

    public static function fromReceived(ReceivedResponse $response): self
    {
        return new self($response->id);
    }
}
