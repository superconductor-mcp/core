<?php

namespace Fabricate\Schema\V20260728\Messaging\MetaObjects;

use Fabricate\Contracts\Schema\RequestId;
use Fabricate\Schema\V20260728\MetaObject;

readonly class NotificationMetaObject extends MetaObject
{
    /**
     * @param array<string,mixed> $record
     */
    public function __construct(
        ?RequestId $subscription_id = null,
        array $record = [],
    ) {
        if(isset($subscription_id)) {
            $record['io.modelcontextprotocol/subscriptionId'] = $subscription_id->id;
        }

        parent::__construct($record);
    }
}
