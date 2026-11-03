<?php

namespace Fabricate\Schema\V20260728\Messaging\Tasks;

use Fabricate\Contracts\Schema\Messaging\ClientRequest;
use Fabricate\Contracts\Schema\Messaging\Tasks\GetTaskRequest as GetTaskContract;
use Fabricate\Contracts\Schema\RequestId;
use Fabricate\Schema\Common\Messaging\RPC\JSONRPCRequest;
use Fabricate\Schema\V20260728\Messaging\Params\TaskRequestParams;

final readonly class GetTaskRequest extends JSONRPCRequest implements GetTaskContract, ClientRequest
{
    public function __construct(
        RequestId $id,
        TaskRequestParams $params,
    ) {
        parent::__construct("2.0", $id, "tasks/get", $params);
    }

    public function taskId(): string
    {
        return $this->params->taskId;
    }
}
