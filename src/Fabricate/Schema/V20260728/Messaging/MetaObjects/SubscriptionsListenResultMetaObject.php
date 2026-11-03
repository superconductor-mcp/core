<?php

namespace Fabricate\Schema\V20260728\Messaging\MetaObjects;

use Fabricate\Contracts\Schema\RequestId;
use Fabricate\Schema\V20260728\Implementation;

readonly class SubscriptionsListenResultMetaObject extends ResultMetaObject
{
    /**
     * @param array<string,mixed> $record
     */
    public function __construct(
        RequestId $subscription_id,
        ?Implementation $server_info = null,
        array $record = [],
    ) {
        $record['io.modelcontextprotocol/subscriptionId'] = $subscription_id->id;

        parent::__construct($server_info, $record);
    }
}
