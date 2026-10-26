<?php

namespace Fabricate\NutsAndBolts\Messages\Resources;

use Fabricate\NutsAndBolts\Messages\MCPNotification;

// sent by a server that advertised resources.subscribe when a resource a client subscribed to changes
final readonly class ResourceUpdatedNotification extends MCPNotification
{
    public function __construct(
        public string $uri,
    ) {
        parent::__construct('notifications/resources/updated', ['uri' => $uri]);
    }

    public function toJson(): string
    {
        return json_encode([
            'jsonrpc' => '2.0',
            'method' => $this->method,
            'params' => ['uri' => $this->uri],
        ]);
    }

    public static function generate(mixed $params): ?self
    {
        $uri = is_array($params) ? ($params['uri'] ?? null) : null;

        return is_string($uri) && $uri !== '' ? new self($uri) : null;
    }
}
