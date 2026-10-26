<?php

namespace Fabricate\NutsAndBolts\Messages\Tools;

use Fabricate\Contracts\NutsAndBolts\MCPError;
use Fabricate\NutsAndBolts\Messages\Errors\InvalidParams;
use Fabricate\NutsAndBolts\Messages\Errors\InvalidRequest;
use Fabricate\NutsAndBolts\Messages\MCPRequest;

final readonly class CallToolRequest extends MCPRequest
{
    /**
     * @param array<string, mixed> $arguments the tool's arguments, keyed by parameter name
     */
    public function __construct(
        string|int $id,
        public string $name,
        public array $arguments = [],
    ) {
        parent::__construct(
            $id,
            'tools/call',
            ['name' => $name, 'arguments' => $arguments],
        );
    }

    public static function generate(mixed $id, mixed $method, mixed $params, mixed $jsonrpc = "2.0",): MCPRequest|MCPError
    {
        // the envelope first: until the id is proven, an error cannot echo it
        if (! is_string($id) && ! is_int($id)) {
            return new InvalidRequest(null, 'tools/call needs a string or integer id.');
        }

        if ($jsonrpc !== '2.0') {
            return new InvalidRequest($id, 'jsonrpc must be "2.0".');
        }

        // tools/call always names a tool, so params are required
        if (! CallToolRequest::isObject($params)) {
            return new InvalidParams($id, 'params must be an object.');
        }

        $name = $params['name'] ?? null;
        $arguments = $params['arguments'] ?? [];

        if (! is_string($name) || $name === '') {
            return new InvalidParams($id, 'params.name must be a non-empty string.');
        }

        if (! CallToolRequest::isObject($arguments)) {
            return new InvalidParams($id, 'params.arguments must be an object when present.');
        }

        return new CallToolRequest($id, $name, $arguments);
    }

    public function toJson(): string
    {
        return json_encode([
            'jsonrpc' => '2.0',
            'id' => $this->id,
            'method' => $this->method,
            'params' => ['name' => $this->name, 'arguments' => static::object($this->arguments)],
        ]);
    }
}
