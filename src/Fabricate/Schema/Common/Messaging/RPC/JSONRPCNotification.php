<?php

namespace Fabricate\Schema\Common\Messaging\RPC;

use Fabricate\Schema\Common\Messaging\Notification;
use Fabricate\Contracts\Core\SuperconductorMCPException;
use Fabricate\Contracts\Schema\Messaging\RPC\JSONRPCNotification as NotificationContract;

abstract readonly class JSONRPCNotification extends Notification implements NotificationContract
{
    public function __construct(
        public string $jsonrpc,
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
        return ['jsonrpc' => $this->jsonrpc] + parent::toArray();
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }
}
