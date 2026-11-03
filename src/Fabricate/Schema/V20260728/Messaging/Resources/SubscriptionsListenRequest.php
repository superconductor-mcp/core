<?php

namespace Fabricate\Schema\V20260728\Messaging\Resources;

use Fabricate\Contracts\Schema\Messaging\Resources\SubscriptionsListenRequest as SubscriptionsListenRequestContract;
use Fabricate\Contracts\Schema\Messaging\ClientRequest;
use Fabricate\Contracts\Schema\RequestId;
use Fabricate\Schema\Common\Messaging\RPC\JSONRPCRequest;
use Fabricate\Schema\V20260728\Messaging\Params\SubscriptionsListenRequestParams;

final readonly class SubscriptionsListenRequest extends JSONRPCRequest implements SubscriptionsListenRequestContract, ClientRequest
{
    public function __construct(
        RequestId $id,
        SubscriptionsListenRequestParams $params,
    ) {
        parent::__construct("2.0", $id, 'subscriptions/listen', $params);
    }
}
