<?php

namespace Fabricate\Mechanism\Signals;

use Fabricate\Contracts\Schema\RequestId;

/**
 * Fired when a request an entity sent went unanswered past its configuration's request timeout. The entity sent
 * notifications/cancelled for it and no longer waits for its response.
 */
final readonly class RequestTimedOut
{
    public function __construct(
        public string $session_id,
        public RequestId $id,
        public string $method,
    ) {}
}
