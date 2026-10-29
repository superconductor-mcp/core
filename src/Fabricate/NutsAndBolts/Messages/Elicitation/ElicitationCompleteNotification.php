<?php

namespace Fabricate\NutsAndBolts\Messages\Elicitation;

use Fabricate\NutsAndBolts\Messages\MCPNotification;

// 2025-11-25, server to client: the out-of-band interaction a URL elicitation started is done
final readonly class ElicitationCompleteNotification extends MCPNotification
{
    public function __construct(
        public string $elicitation_id,
    ) {
        parent::__construct('notifications/elicitation/complete', ['elicitationId' => $elicitation_id]);
    }

    public function toJson(): string
    {
        return json_encode([
            'jsonrpc' => '2.0',
            'method' => $this->method,
            'params' => ['elicitationId' => $this->elicitation_id],
        ]);
    }

    public static function generate(mixed $params): ?self
    {
        $id = is_array($params) ? ($params['elicitationId'] ?? null) : null;

        return is_string($id) && $id !== '' ? new self($id) : null;
    }
}
