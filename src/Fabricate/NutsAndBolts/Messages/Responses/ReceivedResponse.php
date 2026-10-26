<?php

namespace Fabricate\NutsAndBolts\Messages\Responses;

use Fabricate\Contracts\NutsAndBolts\MCPError;
use Fabricate\NutsAndBolts\Messages\Errors\InvalidRequest;
use Fabricate\NutsAndBolts\Messages\MCPResponse;

// a response from the other side to a request this side sent, e.g. the client's answer to a server ping
final readonly class ReceivedResponse extends MCPResponse
{
    public function __construct(
        string|int $id,
        ?array $result = null,
        ?array $error = null,
    ) {
        parent::__construct($id, $result, $error);
    }

    // takes the raw decoded line: a response carries an id and exactly one of result or error
    public static function generate(array $decoded): ReceivedResponse|MCPError
    {
        $id = $decoded['id'] ?? null;

        if (! is_string($id) && ! is_int($id)) {
            return new InvalidRequest(null, 'A response needs a string or integer id.');
        }

        if (($decoded['jsonrpc'] ?? null) !== '2.0') {
            return new InvalidRequest($id, 'jsonrpc must be "2.0".');
        }

        $has_result = array_key_exists('result', $decoded);
        $has_error = array_key_exists('error', $decoded);

        if ($has_result === $has_error) {
            return new InvalidRequest($id, 'A response must carry exactly one of result or error.');
        }

        if ($has_result && ! is_array($decoded['result'])) {
            return new InvalidRequest($id, 'result must be an object.');
        }

        if ($has_error && (! is_array($decoded['error']) || ! is_int($decoded['error']['code'] ?? null) || ! is_string($decoded['error']['message'] ?? null))) {
            return new InvalidRequest($id, 'error must be an object with an integer code and a string message.');
        }

        return new ReceivedResponse($id, $has_result ? $decoded['result'] : null, $has_error ? $decoded['error'] : null);
    }

    public function toJson(): string
    {
        return json_encode(array_filter([
            'jsonrpc' => '2.0',
            'id' => $this->id,
            'result' => is_null($this->result) ? null : (object) $this->result,
            'error' => $this->error,
        ], fn (mixed $value): bool => ! is_null($value)));
    }
}
