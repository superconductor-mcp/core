<?php

namespace Fabricate\Mechanism\Decoding;

use Fabricate\Contracts\Mechanism\DecodedMessage;
use Fabricate\Contracts\Schema\RequestId;

/** An inbound successful response to a request this side sent. */
final readonly class DecodedResponse implements DecodedMessage
{
    public function __construct(
        public RequestId $id,
        public mixed $result,
    ) {}

    public function id(): RequestId
    {
        return $this->id;
    }
}
