<?php

namespace Fabricate\Mechanism\Decoding;

use Fabricate\Contracts\Schema\RequestId;
use Fabricate\Contracts\Mechanism\DecodedMessage;

/** An inbound notification: it carries no id and is never answered. */
final readonly class DecodedNotification implements DecodedMessage
{
    /**
     * @param array<mixed>|null $params The notification's params object, or null when the message has none.
     */
    public function __construct(
        public string $method,
        public ?array $params = null,
    ) {}

    public function id(): ?RequestId
    {
        return null;
    }
}
