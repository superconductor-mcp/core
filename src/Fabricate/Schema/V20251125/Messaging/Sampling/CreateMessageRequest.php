<?php

namespace Fabricate\Schema\V20251125\Messaging\Sampling;

use Fabricate\Contracts\Schema\Messaging\Sampling\CreateMessageRequest as CreateMessageContract;
use Fabricate\Contracts\Schema\Messaging\ServerRequest;
use Fabricate\Contracts\Schema\RequestId;
use Fabricate\Schema\Common\Messaging\RPC\JSONRPCRequest;
use Fabricate\Schema\V20251125\Messaging\Params\CreateMessageRequestParams;

final readonly class CreateMessageRequest extends JSONRPCRequest implements CreateMessageContract, ServerRequest
{
    public function __construct(
        RequestId $id,
        CreateMessageRequestParams $params,
    ) {
        parent::__construct("2.0", $id, 'sampling/createMessage', $params);
    }
}
