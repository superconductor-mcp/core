<?php

namespace Fabricate\NutsAndBolts\Messages\Ping;

use Fabricate\Contracts\NutsAndBolts\MCPError;
use Fabricate\NutsAndBolts\Messages\Errors\InvalidParams;
use Fabricate\NutsAndBolts\Messages\Errors\InvalidRequest;
use Fabricate\NutsAndBolts\Messages\MCPRequest;

final readonly class PingRequest extends MCPRequest
{
    public function __construct(
        string|int $id,
    ){
        parent::__construct($id, 'ping');
    }

    public static function generate(mixed $id, mixed $method, mixed $params, mixed $jsonrpc = "2.0"): MCPRequest|MCPError
    {
        // the envelope first: until the id is proven, an error cannot echo it
        if (! is_string($id) && ! is_int($id)) {
            return new InvalidRequest(null, 'ping needs a string or integer id.');
        }

        if ($jsonrpc !== '2.0') {
            return new InvalidRequest($id, 'jsonrpc must be "2.0".');
        }

        // ping takes no params; when present they can only be an object (e.g. carrying _meta)
        if (! is_null($params) && ! PingRequest::isObject($params)) {
            return new InvalidParams($id, 'params must be an object when present.');
        }

        return new PingRequest($id);
    }

    // the server's own ping: no params, just the id it will match the client's answer against
    public function toJson(): string
    {
        return json_encode([
            'jsonrpc' => '2.0',
            'id' => $this->id,
            'method' => $this->method,
        ]);
    }
}
