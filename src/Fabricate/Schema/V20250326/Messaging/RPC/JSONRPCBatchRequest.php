<?php

namespace Fabricate\Schema\V20250326\Messaging\RPC;

use Voyager\Contracts\NutsAndBolts\Arrayable;

readonly class JSONRPCBatchRequest implements Arrayable
{
    /**
     * @param (JSONRPCRequest|JSONRPCNotification)[] $messages
     */
    public function __construct(
        public array $messages,
    ) {}

    public function toArray(): array
    {
        return array_map(fn(JSONRPCRequest|JSONRPCNotification $message) => $message->toArray(), $this->messages);
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }
}
