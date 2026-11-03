<?php

namespace Fabricate\Schema\Common\Messaging\RPC;

use Fabricate\Contracts\Core\SuperconductorMCPException;
use Fabricate\Contracts\Schema\Messaging\RPC\JSONRPCResponse as ResponseContract;
use Fabricate\Contracts\Schema\RequestId;
use Fabricate\Schema\Common\Messaging\MCPError;
use Fabricate\Schema\Common\Messaging\Result;

abstract readonly class JSONRPCResponse implements ResponseContract
{
    public function __construct(
        public string $jsonrpc,
        public ?RequestId $id = null,
        public ?Result $result = null,
        public ?MCPError $error = null,
    ) {
        if($this->jsonrpc !== "2.0")
        {
            throw new SuperconductorMCPException(static::class.'- jsonrpc must be "2.0" exactly.');
        }

        if((!isset($this->id)) && (!isset($this->error)))
        {
            throw new SuperconductorMCPException(static::class.'- id is required unless error has data.');
        }

    }

    public function jsonrpc(): string
    {
        return $this->jsonrpc;
    }

    public function result(): mixed
    {
        return $this->result;
    }

    public function toArray(): array
    {
        $results = ['jsonrpc' => $this->jsonrpc];

        if(isset($this->id)) $results['id'] = $this->id->id;
        if(isset($this->result)) $results['result'] = $this->result->toArray() ?: (object) [];
        if(isset($this->error)) $results['error'] = $this->error->toArray();

        return $results;
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }
}
