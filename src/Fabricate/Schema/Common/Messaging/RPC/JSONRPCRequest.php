<?php

namespace Fabricate\Schema\Common\Messaging\RPC;

use Fabricate\Contracts\Schema\RequestId;
use Fabricate\Schema\Common\Messaging\Request;
use Fabricate\Contracts\Core\SuperconductorMCPException;
use Fabricate\Contracts\Schema\Messaging\RPC\JSONRPCRequest as RequestContract;

abstract readonly class JSONRPCRequest extends Request implements RequestContract
{
    public function __construct(
        public string $jsonrpc,
        public RequestId $id,
        string $method,
        mixed $params = null
    ) {
        if($this->jsonrpc !== "2.0")
        {
            throw new SuperconductorMCPException(static::class.'- jsonrpc must be "2.0" exactly.');
        }

        parent::__construct($method, $params);
    }

    public function jsonrpc(): string
    {
        return $this->jsonrpc;
    }

    public function toArray(): array
    {
        return ['jsonrpc' => $this->jsonrpc, 'id' => $this->id->id] + parent::toArray();
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }
}
