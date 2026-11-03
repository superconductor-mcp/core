<?php

namespace Fabricate\Schema\V20260728\Messaging\Params;

use Fabricate\Contracts\Schema\RequestId;
use Fabricate\Schema\V20260728\Messaging\MetaObjects\NotificationMetaObject;

final readonly class CancelledNotificationParams extends NotificationParams
{
    public function __construct(
        public RequestId $requestId,
        public ?string $reason = null,
        ?NotificationMetaObject $_meta = null
    ) {
        parent::__construct($_meta);
    }

    public function toArray(): array
    {
        $results = parent::toArray();

        $results['requestId'] = $this->requestId->id;
        if(isset($this->reason)) $results['reason'] = $this->reason;

        return $results;
    }
}
