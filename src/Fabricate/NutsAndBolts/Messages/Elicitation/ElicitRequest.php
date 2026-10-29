<?php

namespace Fabricate\NutsAndBolts\Messages\Elicitation;

use Fabricate\Contracts\NutsAndBolts\MCPError;
use InvalidArgumentException;
use Fabricate\NutsAndBolts\Schema\ObjectSchema;
use Fabricate\NutsAndBolts\Messages\MCPRequest;
use Fabricate\NutsAndBolts\Messages\Errors\InvalidParams;
use Fabricate\NutsAndBolts\Messages\Errors\InvalidRequest;

/**
 * Server to client: ask the user. Form mode (2025-06-18) asks for a few values described by a flat schema;
 * URL mode (2025-11-25) sends the user to a URL for what must not pass through the client, the
 * elicitationId tying the request to the out-of-band interaction and its completion notification.
 */
final readonly class ElicitRequest extends MCPRequest
{
    public string $mode;

    public function __construct(
        string|int $id,
        public string $message,
        public ?ObjectSchema $requested_schema = null,
        public ?string $url = null,
        public ?string $elicitation_id = null,
    ) {
        $this->mode = is_null($url) ? 'form' : 'url';

        if ($this->mode === 'form') {
            if (is_null($requested_schema) || ! is_null($elicitation_id)) {
                throw new InvalidArgumentException('a form elicitation takes a requested schema and no elicitation id.');
            }

            if (! $requested_schema->isFlat()) {
                throw new InvalidArgumentException('elicitation takes only string, number, integer, boolean and enum properties.');
            }
        } else {
            if (! is_null($requested_schema)) {
                throw new InvalidArgumentException('a URL elicitation takes no requested schema.');
            }

            if (is_null($elicitation_id) || $elicitation_id === '') {
                throw new InvalidArgumentException('a URL elicitation needs a non-empty elicitation id.');
            }

            if (filter_var($url, FILTER_VALIDATE_URL) === false) {
                throw new InvalidArgumentException("[{$url}] is not a valid URL.");
            }
        }

        // form mode leaves `mode` out: a client without URL mode, or before 2025-11-25, never saw the field
        parent::__construct($id, 'elicitation/create', $this->mode === 'form'
            ? ['message' => $message, 'requestedSchema' => $requested_schema->toArray()]
            : ['mode' => 'url', 'message' => $message, 'elicitationId' => $elicitation_id, 'url' => $url]);
    }

    public static function url(string|int $id, string $message, string $url, string $elicitation_id): self
    {
        return new self($id, $message, null, $url, $elicitation_id);
    }

    public static function generate(mixed $id, mixed $method, mixed $params, mixed $jsonrpc = '2.0'): MCPRequest|MCPError
    {
        if (! is_string($id) && ! is_int($id)) {
            return new InvalidRequest(null, 'elicitation/create needs a string or integer id.');
        }

        if (! ElicitRequest::isObject($params) || ! is_string($params['message'] ?? null)) {
            return new InvalidParams($id, 'params needs a string message.');
        }

        $mode = $params['mode'] ?? 'form';

        try {
            return match ($mode) {
                // a request without a mode is a form, as before 2025-11-25
                'form' => is_array($params['requestedSchema'] ?? null)
                    ? new ElicitRequest($id, $params['message'], ObjectSchema::fromArray($params['requestedSchema']))
                    : new InvalidParams($id, 'a form elicitation needs an object requestedSchema.'),
                'url' => is_string($params['url'] ?? null) && is_string($params['elicitationId'] ?? null)
                    ? ElicitRequest::url($id, $params['message'], $params['url'], $params['elicitationId'])
                    : new InvalidParams($id, 'a URL elicitation needs a string url and elicitationId.'),
                default => new InvalidParams($id, 'mode must be form or url.'),
            };
        } catch (InvalidArgumentException $e) {
            return new InvalidParams($id, "elicitation/create: {$e->getMessage()}");
        }
    }
}
