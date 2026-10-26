<?php

namespace Fabricate\NutsAndBolts\Messages\Resources;

use Fabricate\Contracts\NutsAndBolts\MCPError;
use Fabricate\NutsAndBolts\Messages\Errors\InvalidParams;
use Fabricate\NutsAndBolts\Messages\Errors\InvalidRequest;
use Fabricate\NutsAndBolts\Messages\MCPRequest;

final readonly class UnsubscribeRequest extends MCPRequest
{
    public function __construct(
        string|int $id,
        public string $uri,
    ) {
        parent::__construct($id, 'resources/unsubscribe', ['uri' => $uri]);
    }

    public static function generate(mixed $id, mixed $method, mixed $params, mixed $jsonrpc = "2.0",): MCPRequest|MCPError
    {
        // the envelope first: until the id is proven, an error cannot echo it
        if (! is_string($id) && ! is_int($id)) {
            return new InvalidRequest(null, 'resources/unsubscribe needs a string or integer id.');
        }

        if ($jsonrpc !== '2.0') {
            return new InvalidRequest($id, 'jsonrpc must be "2.0".');
        }

        // resources/unsubscribe always names a uri, so params are required
        if (! UnsubscribeRequest::isObject($params)) {
            return new InvalidParams($id, 'params must be an object.');
        }

        $uri = $params['uri'] ?? null;

        if (! is_string($uri) || $uri === '') {
            return new InvalidParams($id, 'params.uri must be a non-empty string.');
        }

        return new UnsubscribeRequest($id, $uri);
    }
}
