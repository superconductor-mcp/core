<?php

namespace Fabricate\NutsAndBolts\Messages\Initialize;

use Fabricate\NutsAndBolts\Messages\Errors\InvalidParams;
use Fabricate\NutsAndBolts\Messages\Errors\InvalidRequest;
use Fabricate\NutsAndBolts\Messages\Errors\JsonRpcError;
use Fabricate\NutsAndBolts\Messages\MCPRequest;

final readonly class InitializeRequest extends MCPRequest
{
    public function __construct(
        string|int $id,
        public string $protocol_version,
        public array $capabilities = [],
        public array $client_info = [],
    ) {
        parent::__construct(
            $id,
            'initialize',
            [
                'protocolVersion' => $protocol_version,
                'capabilities' => $capabilities,
                'clientInfo' => $client_info,
            ],
        );
    }

    public static function generate(
        mixed $id,
        mixed $method,
        mixed $params,
        mixed $jsonrpc = "2.0",
    ): InitializeRequest|JsonRpcError
    {
        // the envelope first: until the id is proven, an error cannot echo it
        if (! is_string($id) && ! is_int($id)) {
            return new InvalidRequest(null, 'initialize needs a string or integer id.');
        }

        if ($jsonrpc !== '2.0') {
            return new InvalidRequest($id, 'jsonrpc must be "2.0".');
        }

        if (! InitializeRequest::isObject($params)) {
            return new InvalidParams($id, 'params must be an object.');
        }

        $protocol_version = $params['protocolVersion'] ?? null;
        $capabilities = $params['capabilities'] ?? null;
        $client_info = $params['clientInfo'] ?? null;

        if (! is_string($protocol_version)) {
            return new InvalidParams($id, 'params.protocolVersion must be a string.');
        }

        if (! InitializeRequest::isObject($capabilities)) {
            return new InvalidParams($id, 'params.capabilities must be an object.');
        }

        if (! InitializeRequest::isObject($client_info)
            || ! is_string($client_info['name'] ?? null)
            || ! is_string($client_info['version'] ?? null)) {
            return new InvalidParams($id, 'params.clientInfo must be an object with a string name and version.');
        }

        return new InitializeRequest($id, $protocol_version, $capabilities, $client_info);
    }

    public function toJson(): string
    {
        return json_encode([
            'jsonrpc' => '2.0',
            'id' => $this->id,
            'method' => $this->method,
            'params' => [
                'protocolVersion' => $this->protocol_version,
                'capabilities' => static::object($this->capabilities),
                'clientInfo' => $this->client_info,
            ],
        ]);
    }
}
