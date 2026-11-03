<?php

namespace Fabricate\Schema\V20251125\Messaging\Elicitation;

use Fabricate\Contracts\Schema\Messaging\Elicitation\ElicitRequest as ElicitContract;
use Fabricate\Contracts\Schema\Messaging\ServerRequest;
use Fabricate\Contracts\Schema\RequestId;
use Fabricate\Schema\Common\Messaging\RPC\JSONRPCRequest;
use Fabricate\Schema\V20251125\Concerns\ElicitRequestParams;

final readonly class ElicitRequest extends JSONRPCRequest implements ElicitContract, ServerRequest
{
    public function __construct(
        RequestId $id,
        ElicitRequestParams $params,
    ) {
        parent::__construct("2.0", $id, 'elicitation/create', $params);
    }
}
