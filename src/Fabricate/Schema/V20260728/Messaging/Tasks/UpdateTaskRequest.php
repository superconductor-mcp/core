<?php

namespace Fabricate\Schema\V20260728\Messaging\Tasks;

use Fabricate\Contracts\Schema\Messaging\ClientRequest;
use Fabricate\Contracts\Schema\Messaging\Tasks\UpdateTaskRequest as UpdateTaskContract;
use Fabricate\Contracts\Schema\RequestId;
use Fabricate\Schema\Common\Messaging\RPC\JSONRPCRequest;
use Fabricate\Schema\V20260728\Messaging\Inputs\InputResponses;
use Fabricate\Schema\V20260728\Messaging\Params\UpdateTaskRequestParams;

final readonly class UpdateTaskRequest extends JSONRPCRequest implements UpdateTaskContract, ClientRequest
{
    public function __construct(
        RequestId $id,
        UpdateTaskRequestParams $params,
    ) {
        parent::__construct("2.0", $id, "tasks/update", $params);
    }

    public function taskId(): string
    {
        return $this->params->taskId;
    }

    public function inputResponses(): InputResponses
    {
        return $this->params->inputResponses;
    }
}
