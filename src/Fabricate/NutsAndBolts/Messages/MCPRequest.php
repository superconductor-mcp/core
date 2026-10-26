<?php

namespace Fabricate\NutsAndBolts\Messages;

use Fabricate\Contracts\NutsAndBolts\MCPError;

abstract readonly class MCPRequest extends RoutedMCPMessage
{
    public function __construct(
        public string|int $id,
        string $method,
        ?array $params = null,
    ) {
        parent::__construct(
            $method,
            $params
        );
    }

    // Takes the raw decoded values: this is where a line is checked, so nothing here is trusted yet.
    abstract public static function generate(
        mixed $id,
        mixed $method,
        mixed $params,
        mixed $jsonrpc = "2.0",
    ): MCPRequest|MCPError;

    // json_decode turns both {} and [] into an empty array; a non-empty list is a JSON array, not an object
    protected static function isObject(mixed $value): bool
    {
        return is_array($value) && ($value === [] || ! array_is_list($value));
    }

    // the wire form of this request: params are left out when there are none
    public function toJson(): string
    {
        $payload = ['jsonrpc' => '2.0', 'id' => $this->id, 'method' => $this->method];

        if (! is_null($this->params)) {
            $payload['params'] = static::object($this->params);
        }

        return json_encode($payload);
    }

    // an empty PHP array would go out as a JSON array where the spec wants an object
    protected static function object(array $value): object|array
    {
        return $value === [] ? new \stdClass() : $value;
    }
}
