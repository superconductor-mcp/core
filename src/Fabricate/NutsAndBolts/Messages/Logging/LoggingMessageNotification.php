<?php

namespace Fabricate\NutsAndBolts\Messages\Logging;

use Fabricate\NutsAndBolts\LogLevel;
use Fabricate\NutsAndBolts\Messages\MCPNotification;

// one log entry from a server that advertised the logging capability
final readonly class LoggingMessageNotification extends MCPNotification
{
    /**
     * @param mixed $data anything JSON can carry: a string, or a structure with context
     */
    public function __construct(
        public LogLevel $level,
        public mixed $data,
        public ?string $logger = null,
    ) {
        parent::__construct('notifications/message', array_filter([
            'level' => $level->value,
            'logger' => $logger,
            'data' => $data,
        ], fn (mixed $value, string $key): bool => $key === 'data' || ! is_null($value), ARRAY_FILTER_USE_BOTH));
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
        $level = is_array($params) ? LogLevel::tryFrom((string) ($params['level'] ?? '')) : null;
        $logger = is_array($params) ? ($params['logger'] ?? null) : null;

        if (is_null($level) || ! array_key_exists('data', $params) || (! is_null($logger) && ! is_string($logger))) {
            return null;
        }

        return new self($level, $params['data'], $logger);
    }
}
