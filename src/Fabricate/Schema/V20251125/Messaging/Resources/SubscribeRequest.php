<?php

namespace Fabricate\Schema\V20251125\Messaging\Resources;

use Fabricate\Contracts\Schema\Messaging\ClientRequest;
use Fabricate\Contracts\Schema\Messaging\Resources\SubscribeRequest as SubscribeContract;
use Fabricate\Contracts\Schema\RequestId;
use Fabricate\Schema\Common\Messaging\RPC\JSONRPCRequest;
use Fabricate\Schema\V20251125\Messaging\Params\SubscribeRequestParams;

final readonly class SubscribeRequest extends JSONRPCRequest implements SubscribeContract, ClientRequest
{
    public function __construct(
        RequestId $id,
        SubscribeRequestParams $params,
    ){
        parent::__construct("2.0", $id, 'resources/subscribe', $params);
    }

    public function uri(): string
    {
        return $this->params->uri;
    }
}
