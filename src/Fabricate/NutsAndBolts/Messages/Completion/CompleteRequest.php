<?php

namespace Fabricate\NutsAndBolts\Messages\Completion;

use Fabricate\Contracts\NutsAndBolts\MCPError;
use Fabricate\NutsAndBolts\Messages\Errors\InvalidParams;
use Fabricate\NutsAndBolts\Messages\Errors\InvalidRequest;
use Fabricate\NutsAndBolts\Messages\MCPRequest;

final readonly class CompleteRequest extends MCPRequest
{
    /**
     * @param string $ref_type ref/prompt or ref/resource
     * @param string $ref the prompt's name, or the resource's uri (a template, e.g. station://sensors/{id})
     */
    public function __construct(
        string|int $id,
        public string $ref_type,
        public string $ref,
        public string $argument_name,
        public string $argument_value,
    ) {
        parent::__construct($id, 'completion/complete', [
            'ref' => ['type' => $ref_type, $ref_type === 'ref/prompt' ? 'name' : 'uri' => $ref],
            'argument' => ['name' => $argument_name, 'value' => $argument_value],
        ]);
    }

    public static function generate(mixed $id, mixed $method, mixed $params, mixed $jsonrpc = "2.0",): MCPRequest|MCPError
    {
        // the envelope first: until the id is proven, an error cannot echo it
        if (! is_string($id) && ! is_int($id)) {
            return new InvalidRequest(null, 'completion/complete needs a string or integer id.');
        }

        if ($jsonrpc !== '2.0') {
            return new InvalidRequest($id, 'jsonrpc must be "2.0".');
        }

        if (! CompleteRequest::isObject($params)) {
            return new InvalidParams($id, 'params must be an object.');
        }

        $ref = $params['ref'] ?? null;
        $argument = $params['argument'] ?? null;

        if (! CompleteRequest::isObject($ref) || ! in_array($ref['type'] ?? null, ['ref/prompt', 'ref/resource'], true)) {
            return new InvalidParams($id, 'params.ref must be an object with type ref/prompt or ref/resource.');
        }

        $key = $ref['type'] === 'ref/prompt' ? 'name' : 'uri';

        if (! is_string($ref[$key] ?? null) || $ref[$key] === '') {
            return new InvalidParams($id, "params.ref.{$key} must be a non-empty string.");
        }

        if (! CompleteRequest::isObject($argument) || ! is_string($argument['name'] ?? null) || ! is_string($argument['value'] ?? null)) {
            return new InvalidParams($id, 'params.argument must be an object with a string name and value.');
        }

        return new CompleteRequest($id, $ref['type'], $ref[$key], $argument['name'], $argument['value']);
    }
}
