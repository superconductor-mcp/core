<?php

namespace Fabricate\Schema\V20260728\Messaging\Resources;

use Fabricate\Contracts\Schema\Messaging\Inputs\InputResponseRequest;
use Fabricate\Schema\V20260728\Messaging\Inputs\InputResponses;
use Fabricate\Contracts\Schema\Messaging\ClientRequest;
use Fabricate\Contracts\Schema\Messaging\Resources\ReadResourceRequest as ReadResourceContract;
use Fabricate\Contracts\Schema\RequestId;
use Fabricate\Schema\Common\Messaging\RPC\JSONRPCRequest;
use Fabricate\Schema\V20260728\Messaging\Params\ReadResourceRequestParams;

final readonly class ReadResourceRequest extends JSONRPCRequest implements ReadResourceContract, ClientRequest, InputResponseRequest
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
        return new static($id, new ReadResourceRequestParams($this->params->uri, $this->params->_meta, $input_responses, $request_state));
    }
}
