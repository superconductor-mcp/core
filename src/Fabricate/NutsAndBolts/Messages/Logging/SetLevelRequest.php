<?php

namespace Fabricate\NutsAndBolts\Messages\Logging;

use Fabricate\Contracts\NutsAndBolts\LogLevel;
use Fabricate\Contracts\NutsAndBolts\MCPError;
use Fabricate\NutsAndBolts\Messages\Errors\InvalidParams;
use Fabricate\NutsAndBolts\Messages\Errors\InvalidRequest;
use Fabricate\NutsAndBolts\Messages\MCPRequest;

final readonly class SetLevelRequest extends MCPRequest
{
    public function __construct(
        string|int $id,
        public LogLevel $level,
    ) {
        parent::__construct($id, 'logging/setLevel', ['level' => $level->value]);
    }

    public static function generate(mixed $id, mixed $method, mixed $params, mixed $jsonrpc = "2.0",): MCPRequest|MCPError
    {
        // the envelope first: until the id is proven, an error cannot echo it
        if (! is_string($id) && ! is_int($id)) {
            return new InvalidRequest(null, 'logging/setLevel needs a string or integer id.');
        }

        if ($jsonrpc !== '2.0') {
            return new InvalidRequest($id, 'jsonrpc must be "2.0".');
        }

        if (! SetLevelRequest::isObject($params)) {
            return new InvalidParams($id, 'params must be an object.');
        }

        // an unknown level is the spec's "invalid log level": -32602
        $level = is_string($params['level'] ?? null) ? LogLevel::tryFrom($params['level']) : null;

        if (is_null($level)) {
            $levels = implode(', ', array_map(fn (LogLevel $level): string => $level->value, LogLevel::cases()));

            return new InvalidParams($id, "params.level must be one of: {$levels}.");
        }

        return new SetLevelRequest($id, $level);
    }
}
