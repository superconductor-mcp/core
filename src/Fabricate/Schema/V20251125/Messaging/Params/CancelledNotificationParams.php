<?php

namespace Fabricate\Schema\V20251125\Messaging\Params;

use Fabricate\Contracts\Schema\RequestId;

final readonly class CancelledNotificationParams extends NotificationParams
{
    public function __construct(
        public ?RequestId $requestId = null,
        public ?string $reason = null,
        ?array $_meta = null
    ) {
        parent::__construct($_meta);
    }

    public function toArray(): array
    {
        $results = parent::toArray();

        if(isset($this->requestId)) $results['requestId'] = $this->requestId->id;
        if(isset($this->reason)) $results['reason'] = $this->reason;

        return $results;
    }
}
