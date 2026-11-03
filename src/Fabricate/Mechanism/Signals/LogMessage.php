<?php

namespace Fabricate\Mechanism\Signals;

use Fabricate\Contracts\Schema\LoggingLevel;
use Fabricate\Contracts\Schema\RequestId;

/**
 * Fired when a tool, prompt or resource logs something while answering a request. The MCPServer of that session sends
 * it as notifications/message, if its configuration declares logging and the level is at or above the one the client
 * set with logging/setLevel, or on 2026-07-28 the one the request named in _meta.
 */
final readonly class LogMessage
{
    public function __construct(
        public string $session_id,
        public RequestId $request,
        public LoggingLevel $level,
        public mixed $data,
        public ?string $logger = null,
        /** On 2026-07-28, the level the request asked for in _meta; null there means it asked for no logging. */
        public ?LoggingLevel $requested_level = null,
    ) {}
}
