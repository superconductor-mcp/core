<?php

namespace Fabricate\Client\Signals;

use Fabricate\Contracts\Schema\Messaging\MCPError;
use Fabricate\Contracts\Schema\Messaging\Result;
use Fabricate\Contracts\Schema\RequestId;

/**
 * Fired when a server answers a request the client sent and nothing was waiting on it with request().
 */
final readonly class ResponseReceived
{
    public function __construct(
        public string $session_id,
        public RequestId $id,
        public string $method,
        public Result|MCPError $response,
    ) {}
}
