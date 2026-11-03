<?php

namespace Fabricate\Client\Signals;

/**
 * Fired when a client's connection breaks after its handshake: the server exited, its stream ended, or the transport
 * could not reach it. The connection's client is gone, every request it had in flight was answered with an error
 * carrying the same reason, and failure() on the connection says it too.
 */
final readonly class ConnectionFailed
{
    public function __construct(
        public string $session_id,
        public string $reason,
    ) {}
}
