<?php

namespace Fabricate\Mechanism\Decoding;

use Fabricate\Contracts\Mechanism\DecodedMessage;
use Fabricate\Contracts\Schema\RequestId;

/** An inbound error response. The id is null when the peer could not read the id of the request it rejects. */
final readonly class DecodedError implements DecodedMessage
{
    public function __construct(
        public ?RequestId $id,
        public int $code,
        public string $message,
        public mixed $data = null,
    ) {}

    public function id(): ?RequestId
    {
        return $this->id;
    }
}
