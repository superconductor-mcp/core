<?php

namespace Fabricate\Schema\V20251125\Messaging\Initialize;

use Fabricate\Contracts\Schema\Messaging\ClientRequest;
use Fabricate\Contracts\Schema\Messaging\Initialize\InitializeRequest as InitializeContract;
use Fabricate\Contracts\Schema\RequestId;
use Fabricate\Schema\Common\Messaging\RPC\JSONRPCRequest;
use Fabricate\Schema\V20251125\Messaging\Params\InitializeRequestParams;

final readonly class InitializeRequest extends JSONRPCRequest implements InitializeContract, ClientRequest
{
    public function __construct(
        RequestId $id,
        InitializeRequestParams $params,
    ) {
        parent::__construct(
            "2.0",
            $id,
            'initialize',
            $params,
        );
    }

    public function protocolVersion(): string
    {
        return $this->params->protocolVersion;
    }
}
