<?php

namespace Fabricate\Contracts\Client;

// one open line to one server; a transport package provides the concrete and hands decoded messages to its client
interface Connection
{
    // starts the connection and joins the event loop; throws when it cannot start at all
    public function open(): void;

    // one JSON-RPC message, without its trailing newline
    public function send(string $line): void;

    // ends the connection in the transport's own order and leaves the event loop
    public function close(): void;

    public function closed(): bool;

    // how this connection would be described in an error: the command line, the url
    public function describe(): string;
}
