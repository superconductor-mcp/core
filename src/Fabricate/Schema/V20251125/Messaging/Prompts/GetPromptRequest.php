<?php

namespace Fabricate\Schema\V20251125\Messaging\Prompts;

use Fabricate\Contracts\Schema\Messaging\ClientRequest;
use Fabricate\Contracts\Schema\Messaging\Prompts\GetPromptRequest as GetPromptContract;
use Fabricate\Contracts\Schema\RequestId;
use Fabricate\Schema\Common\Messaging\RPC\JSONRPCRequest;
use Fabricate\Schema\V20251125\Messaging\Params\GetPromptRequestParams;

final readonly class GetPromptRequest extends JSONRPCRequest implements GetPromptContract, ClientRequest
{
    public function __construct(
        RequestId $id,
        GetPromptRequestParams $params,
    ) {
        parent::__construct("2.0", $id, 'prompts/get', $params);
    }

    public function name(): string
    {
        return $this->params->name;
    }

    public function arguments(): ?array
    {
        return $this->params->arguments;
    }
}
