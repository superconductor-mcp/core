<?php

namespace Fabricate\Mechanism\Decoding;

use Fabricate\Contracts\Mechanism\DecodedMessage;
use Fabricate\Contracts\Schema\RequestId;

/** An inbound request: it carries an id and expects a response. */
final readonly class DecodedRequest implements DecodedMessage
{
    /**
     * @param array<mixed>|null $params The request's params object, or null when the message has none.
     */
    public function __construct(
        public RequestId $id,
        public string $method,
        public ?array $params = null,
    ) {}

    public function id(): RequestId
    {
        return $this->id;
    }
}
