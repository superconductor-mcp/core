<?php

namespace Fabricate\Schema\V20251125\Messaging\Logging;

use Fabricate\Contracts\Schema\LoggingLevel;
use Fabricate\Contracts\Schema\Messaging\ClientRequest;
use Fabricate\Contracts\Schema\Messaging\Logging\SetLevelRequest as SetLevelContract;
use Fabricate\Contracts\Schema\RequestId;
use Fabricate\Schema\Common\Messaging\RPC\JSONRPCRequest;
use Fabricate\Schema\V20251125\Messaging\Params\SetLevelRequestParams;

final readonly class SetLevelRequest extends JSONRPCRequest implements SetLevelContract, ClientRequest
{
    public function __construct(
        RequestId $id,
        SetLevelRequestParams $params,
    ) {
        parent::__construct("2.0", $id, "logging/setLevel", $params);
    }

    public function level(): LoggingLevel
    {
        return $this->params->level;
    }
}
