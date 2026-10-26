<?php

namespace Fabricate\NutsAndBolts\Messages\Resources;

use Fabricate\Contracts\NutsAndBolts\MCPError;
use Fabricate\NutsAndBolts\Messages\Errors\InvalidParams;
use Fabricate\NutsAndBolts\Messages\Errors\InvalidRequest;
use Fabricate\NutsAndBolts\Messages\MCPRequest;

final readonly class ListResourceTemplatesRequest extends MCPRequest
{
    public function __construct(
        string|int $id,
        public ?string $cursor = null,
    ) {
        parent::__construct(
            $id,
            'resources/templates/list',
            is_null($cursor) ? null : ['cursor' => $cursor],
        );
    }

    public static function generate(mixed $id, mixed $method, mixed $params, mixed $jsonrpc = "2.0",): MCPRequest|MCPError
    {
        // the envelope first: until the id is proven, an error cannot echo it
        if (! is_string($id) && ! is_int($id)) {
            return new InvalidRequest(null, 'resources/templates/list needs a string or integer id.');
        }

        if ($jsonrpc !== '2.0') {
            return new InvalidRequest($id, 'jsonrpc must be "2.0".');
        }

        // params are optional; when present they are an object that may carry a pagination cursor
        if (! is_null($params) && ! ListResourceTemplatesRequest::isObject($params)) {
            return new InvalidParams($id, 'params must be an object when present.');
        }

        $cursor = $params['cursor'] ?? null;

        if (! is_null($cursor) && ! is_string($cursor)) {
            return new InvalidParams($id, 'params.cursor must be a string when present.');
        }

        return new ListResourceTemplatesRequest($id, $cursor);
    }
}
