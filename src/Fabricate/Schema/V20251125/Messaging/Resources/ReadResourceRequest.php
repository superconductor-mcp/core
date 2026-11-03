<?php

namespace Fabricate\Schema\V20251125\Messaging\Resources;

use Fabricate\Contracts\Schema\Messaging\ClientRequest;
use Fabricate\Contracts\Schema\Messaging\Resources\ReadResourceRequest as ReadResourceContract;
use Fabricate\Contracts\Schema\RequestId;
use Fabricate\Schema\Common\Messaging\RPC\JSONRPCRequest;
use Fabricate\Schema\V20251125\Messaging\Params\ReadResourceRequestParams;

final readonly class ReadResourceRequest extends JSONRPCRequest implements ReadResourceContract, ClientRequest
{
    public function __construct(
        RequestId $id,
        ReadResourceRequestParams $params,
    ) {
        parent::__construct("2.0", $id, "resources/read", $params);
    }

    public function uri(): string
    {
        return $this->params->uri;
    }
}
