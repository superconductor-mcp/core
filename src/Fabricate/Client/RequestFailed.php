<?php

namespace Fabricate\Client;

use RuntimeException;

// why a request never got its result: the server answered with a JSON-RPC error, or the connection ended first
final class RequestFailed extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?int $rpc_code = null,
        public readonly mixed $data = null,
    ) {
        parent::__construct($message, $rpc_code ?? 0);
    }
}
