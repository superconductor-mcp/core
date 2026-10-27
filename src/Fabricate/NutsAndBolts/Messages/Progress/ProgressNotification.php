<?php

namespace Fabricate\NutsAndBolts\Messages\Progress;

use Fabricate\NutsAndBolts\Messages\MCPNotification;

// how far a request that carried a progressToken has got
final readonly class ProgressNotification extends MCPNotification
{
    public function __construct(
        public string|int $progress_token,
        public float|int $progress,
        public float|int|null $total = null,
        // 2025-03-26: a line describing where the work is
        public ?string $message = null,
    ) {
        parent::__construct('notifications/progress', array_filter([
            'progressToken' => $progress_token,
            'progress' => $progress,
            'total' => $total,
            'message' => $message,
        ], fn (mixed $value): bool => ! is_null($value)));
    }

    public function toJson(): string
    {
        return json_encode([
            'jsonrpc' => '2.0',
            'method' => $this->method,
            'params' => $this->params,
        ]);
    }

    public static function generate(mixed $params): ?self
    {
        $token = is_array($params) ? ($params['progressToken'] ?? null) : null;
        $progress = is_array($params) ? ($params['progress'] ?? null) : null;
        $total = is_array($params) ? ($params['total'] ?? null) : null;
        $message = is_array($params) ? ($params['message'] ?? null) : null;

        if ((! is_string($token) && ! is_int($token)) || (! is_int($progress) && ! is_float($progress)) || (! is_null($total) && ! is_int($total) && ! is_float($total)) || (! is_null($message) && ! is_string($message))) {
            return null;
        }

        return new self($token, $progress, $total, $message);
    }
}
