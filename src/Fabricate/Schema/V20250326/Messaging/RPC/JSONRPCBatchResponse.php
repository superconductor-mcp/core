<?php

namespace Fabricate\Schema\V20250326\Messaging\RPC;

use Voyager\Contracts\NutsAndBolts\Arrayable;

readonly class JSONRPCBatchResponse implements Arrayable
{
    /**
     * @param (JSONRPCResponse|JSONRPCError)[] $messages
     */
    public function __construct(
        public array $messages,
    ) {}

    public function toArray(): array
    {
        return array_map(fn(JSONRPCResponse|JSONRPCError $message) => $message->toArray(), $this->messages);
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }
}
