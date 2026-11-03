<?php

namespace Fabricate\Schema\V20260728\Messaging\Tools;

use Fabricate\Contracts\Schema\Messaging\Inputs\InputResponseRequest;
use Fabricate\Schema\V20260728\Messaging\Inputs\InputResponses;
use Fabricate\Contracts\Schema\Messaging\ClientRequest;
use Fabricate\Contracts\Schema\Messaging\Tools\CallToolRequest as CallToolContract;
use Fabricate\Contracts\Schema\RequestId;
use Fabricate\Schema\Common\Messaging\RPC\JSONRPCRequest;
use Fabricate\Schema\V20260728\Messaging\Params\CallToolRequestParams;

final readonly class CallToolRequest extends JSONRPCRequest implements CallToolContract, ClientRequest, InputResponseRequest
{
    public function __construct(
        RequestId $id,
        CallToolRequestParams $params,
    ) {
        parent::__construct("2.0", $id, 'tools/call', $params);
    }

    public function name(): string
    {
        return $this->params->name;
    }

    public function arguments(): ?array
    {
        return $this->params->arguments;
    }

    public function runsAsTask(): bool
    {
        return false;
    }

    public function taskTtl(): ?int
    {
        return null;
    }

    public function inputResponses(): ?InputResponses
    {
        return $this->params->inputResponses;
    }

    public function requestState(): ?string
    {
        return $this->params->requestState;
    }

    public function retry(RequestId $id, InputResponses $input_responses, ?string $request_state): static
    {
        return new static($id, new CallToolRequestParams($this->params->name, $this->params->_meta, $this->params->arguments, $input_responses, $request_state));
    }
}
