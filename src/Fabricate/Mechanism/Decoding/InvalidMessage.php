<?php

namespace Fabricate\Mechanism\Decoding;

use Fabricate\Contracts\Mechanism\DecodedMessage;
use Fabricate\Contracts\Schema\RPCErrorCode;
use Fabricate\Contracts\Schema\RequestId;

/** An inbound message that failed the envelope checks, holding what the error reply to it needs. */
final readonly class InvalidMessage implements DecodedMessage
{
    public function __construct(
        public ?RequestId $id,
        public RPCErrorCode $code,
        public string $message,
    ) {}

    public function id(): ?RequestId
    {
        return $this->id;
    }

    public function toArray(): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => $this->id?->id,
            'error' => [
                'code' => $this->code->value,
                'message' => $this->message,
            ],
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }
}
