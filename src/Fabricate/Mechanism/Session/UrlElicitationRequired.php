<?php

namespace Fabricate\Mechanism\Session;

use InvalidArgumentException;
use RuntimeException;
use Fabricate\NutsAndBolts\Capabilities;
use Fabricate\NutsAndBolts\MCPServerBag;
use Fabricate\NutsAndBolts\Messages\Errors\InternalError;
use Fabricate\NutsAndBolts\Messages\Errors\JsonRpcError;
use Fabricate\NutsAndBolts\Messages\Elicitation\ElicitRequest;
use Fabricate\NutsAndBolts\Messages\Errors\UrlElicitationRequiredError;

/**
 * 2025-11-25: thrown by a tool, resource or prompt handler that can't go on until the user finishes one or more
 * URL elicitations. The request is answered with error -32042 listing them; the client may retry once each is done
 * (notifications/elicitation/complete says when). A client without URL mode gets an internal error instead, as
 * the spec allows the -32042 error only where URL elicitation is possible.
 */
final class UrlElicitationRequired extends RuntimeException
{
    /** @var list<array{mode: 'url', message: string, elicitationId: string, url: string}> */
    public readonly array $elicitations;

    /** @param non-empty-list<array{message: string, url: string, elicitationId: string}> $elicitations */
    public function __construct(array $elicitations, string $message = 'This request requires more information.')
    {
        if ($elicitations === []) {
            throw new InvalidArgumentException('a URL elicitation requirement lists at least one elicitation.');
        }

        // each one is checked the way a URL elicitation request is
        $this->elicitations = array_map(function (array $e): array {
            $params = ElicitRequest::url(0, $e['message'] ?? '', $e['url'] ?? '', $e['elicitationId'] ?? '')->params;

            return $params;
        }, $elicitations);

        parent::__construct($message);
    }

    public function toError(string|int $id, MCPServerBag $bag): JsonRpcError
    {
        if (! in_array('url', Capabilities::elicitationModes($bag->client_capabilities ?? [], $bag->protocol_version), true)) {
            return new InternalError($id, "{$this->getMessage()} The client cannot open URL elicitations.");
        }

        return new UrlElicitationRequiredError($id, $this->elicitations, $this->getMessage());
    }
}
