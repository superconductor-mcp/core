<?php

namespace Fabricate\Mechanism\Decoding;

use Fabricate\Contracts\Mechanism\DecodedMessage;

/** An inbound JSON-RPC batch, one decoded message per item in wire order. */
final readonly class DecodedBatch
{
    /** @var list<DecodedMessage> */
    public array $messages;

    public function __construct(DecodedMessage ...$messages)
    {
        $this->messages = array_values($messages);
    }
}
