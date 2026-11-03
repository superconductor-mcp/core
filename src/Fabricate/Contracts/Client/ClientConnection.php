<?php

namespace Fabricate\Contracts\Client;

/**
 * A client's connection to one server over a transport. Opening it runs the transport's handshake; the client exists
 * only once that succeeded.
 */
interface ClientConnection
{
    public function open(): void;

    public function close(): void;

    /** The client talking to the server, or null when the handshake failed or the connection is closed. */
    public function client(): ?Client;

    /** Why the connection failed, or null. */
    public function failure(): ?string;
}
