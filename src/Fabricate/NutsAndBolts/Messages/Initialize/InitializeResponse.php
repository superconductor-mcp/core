<?php

namespace Fabricate\NutsAndBolts\Messages\Initialize;

use Fabricate\NutsAndBolts\Messages\MCPResponse;
use UnexpectedValueException;
use Fabricate\NutsAndBolts\Messages\Responses\ReceivedResponse;

final readonly class InitializeResponse extends MCPResponse
{
    public function __construct(
        string|int $id,
        public string $protocol_version,
        public array $capabilities = [],
        public array $server_info = [],
    ) {
        parent::__construct(
            $id,
            [
                'protocolVersion' => $protocol_version,
                // every capability is a JSON object, even an empty one; a PHP [] would go out as a JSON array
                'capabilities' => json_decode(json_encode($capabilities, JSON_FORCE_OBJECT)),
                'serverInfo' => $server_info,
            ]
        );
    }

    public function toJson(): string
    {
        $payload = [
            'jsonrpc' => '2.0',
            'id' => $this->id,
            'result' => $this->result
        ];

        return json_encode($payload);
    }

    // the server's answer to initialize, as the client received it
    public static function fromReceived(ReceivedResponse $response): self
    {
        $result = $response->result ?? [];

        if (! is_string($result['protocolVersion'] ?? null) || ! is_array($result['capabilities'] ?? null) || ! is_array($result['serverInfo'] ?? null)) {
            throw new UnexpectedValueException('initialize result needs a string protocolVersion and object capabilities and serverInfo.');
        }

        return new self($response->id, $result['protocolVersion'], $result['capabilities'], $result['serverInfo']);
    }
}
