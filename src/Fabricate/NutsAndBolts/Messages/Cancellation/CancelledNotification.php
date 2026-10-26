<?php

namespace Fabricate\NutsAndBolts\Messages\Cancellation;

use Fabricate\NutsAndBolts\Messages\MCPNotification;

// the other side no longer wants the answer to a request it sent
final readonly class CancelledNotification extends MCPNotification
{
    public function __construct(
        public string|int $request_id,
        public ?string $reason = null,
    ) {
        parent::__construct('notifications/cancelled', array_filter([
            'requestId' => $request_id,
            'reason' => $reason,
        ], fn (mixed $value): bool => ! is_null($value)));
    }

    // a malformed cancellation is ignored rather than answered, so a bad one comes back as null
    public static function generate(mixed $params): ?CancelledNotification
    {
        $request_id = is_array($params) ? ($params['requestId'] ?? null) : null;
        $reason = is_array($params) ? ($params['reason'] ?? null) : null;

        if ((! is_string($request_id) && ! is_int($request_id)) || (! is_null($reason) && ! is_string($reason))) {
            return null;
        }

        return new CancelledNotification($request_id, $reason);
    }

    public function toJson(): string
    {
        return json_encode([
            'jsonrpc' => '2.0',
            'method' => $this->method,
            'params' => $this->params,
        ]);
    }
}
