<?php

namespace Fabricate\Schema\V20250618\Messaging\RPC;

use Fabricate\Contracts\Core\SuperconductorMCPException;
use Fabricate\Contracts\Schema\RequestId;
use Fabricate\Contracts\Schema\ResourceErrorCode;
use Fabricate\Contracts\Schema\RPCErrorCode;
use Fabricate\Contracts\Schema\SamplingErrorCode;
use Fabricate\Schema\V20250618\Concerns\JSONRPCError as JSONRPCContract;

readonly class JSONRPCError implements JSONRPCContract
{
    public array $error;

    public function __construct(
        public string $jsonrpc,
        public ?RequestId $id,
        int              $code,
        string           $message,
        mixed            $data = null,
    ) {
        if($this->jsonrpc !== "2.0")
        {
            throw new SuperconductorMCPException(static::class.'- jsonrpc must be "2.0" exactly.');
        }

        if(is_null(RPCErrorCode::tryFrom($code)) && is_null(SamplingErrorCode::tryFrom($code)) && is_null(ResourceErrorCode::tryFrom($code)))
        {
            throw new SuperconductorMCPException(static::class.'- '.$code.' is not a known error code.');
        }

        $error = [
            'code' => $code,
            'message' => $message,
        ];

        if(isset($data)) $error['data'] = $data;

        $this->error = $error;
    }

    public function jsonrpc(): string
    {
        return $this->jsonrpc;
    }

    public function toArray(): array
    {
        return [
            'jsonrpc' => $this->jsonrpc,
            'id' => $this->id?->id,
            'error' => $this->error,
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }
}
