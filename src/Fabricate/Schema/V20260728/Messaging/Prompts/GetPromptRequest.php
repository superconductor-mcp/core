<?php

namespace Fabricate\Schema\V20260728\Messaging\Prompts;

use Fabricate\Contracts\Schema\Messaging\Inputs\InputResponseRequest;
use Fabricate\Schema\V20260728\Messaging\Inputs\InputResponses;
use Fabricate\Contracts\Schema\Messaging\ClientRequest;
use Fabricate\Contracts\Schema\Messaging\Prompts\GetPromptRequest as GetPromptContract;
use Fabricate\Contracts\Schema\RequestId;
use Fabricate\Schema\Common\Messaging\RPC\JSONRPCRequest;
use Fabricate\Schema\V20260728\Messaging\Params\GetPromptRequestParams;

final readonly class GetPromptRequest extends JSONRPCRequest implements GetPromptContract, ClientRequest, InputResponseRequest
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

    public function inputResponses(): ?InputResponses
    {
        return $this->params->inputResponses;
    }

    public function requestState(): ?string
    {
        return $this->params->requestState;
    }
}
