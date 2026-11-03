<?php

namespace Fabricate\Schema\V20251125\Messaging\Resources;

use Fabricate\Contracts\Schema\Messaging\ClientRequest;
use Fabricate\Contracts\Schema\Messaging\Resources\UnsubscribeRequest as UnsubscribeContract;
use Fabricate\Contracts\Schema\RequestId;
use Fabricate\Schema\Common\Messaging\RPC\JSONRPCRequest;
use Fabricate\Schema\V20251125\Messaging\Params\UnsubscribeRequestParams;

final readonly class UnsubscribeRequest extends JSONRPCRequest implements UnsubscribeContract, ClientRequest
{
    public function __construct(
        RequestId $id,
        UnsubscribeRequestParams $params,
    ){
        parent::__construct("2.0", $id, 'resources/unsubscribe', $params);
    }

    public function uri(): string
    {
        return $this->params->uri;
    }
}
