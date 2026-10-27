<?php

namespace Fabricate\NutsAndBolts\Messages\Elicitation;

use Fabricate\Contracts\NutsAndBolts\MCPError;
use InvalidArgumentException;
use Fabricate\NutsAndBolts\Schema\ObjectSchema;
use Fabricate\NutsAndBolts\Messages\MCPRequest;
use Fabricate\NutsAndBolts\Messages\Errors\InvalidParams;
use Fabricate\NutsAndBolts\Messages\Errors\InvalidRequest;

// 2025-06-18, server to client: ask the user for a few values, described by a flat schema
final readonly class ElicitRequest extends MCPRequest
{
    public function __construct(
        string|int $id,
        public string $message,
        public ObjectSchema $requested_schema,
    ) {
        if (! $requested_schema->isFlat()) {
            throw new InvalidArgumentException('elicitation takes only string, number, integer, boolean and enum properties.');
        }

        parent::__construct($id, 'elicitation/create', [
            'message' => $message,
            'requestedSchema' => $requested_schema->toArray(),
        ]);
    }

    public static function generate(mixed $id, mixed $method, mixed $params, mixed $jsonrpc = '2.0'): MCPRequest|MCPError
    {
        if (! is_string($id) && ! is_int($id)) {
            return new InvalidRequest(null, 'elicitation/create needs a string or integer id.');
        }

        if (! ElicitRequest::isObject($params) || ! is_string($params['message'] ?? null) || ! is_array($params['requestedSchema'] ?? null)) {
            return new InvalidParams($id, 'params needs a string message and an object requestedSchema.');
        }

        try {
            return new ElicitRequest($id, $params['message'], ObjectSchema::fromArray($params['requestedSchema']));
        } catch (InvalidArgumentException $e) {
            return new InvalidParams($id, "requestedSchema: {$e->getMessage()}");
        }
    }
}
