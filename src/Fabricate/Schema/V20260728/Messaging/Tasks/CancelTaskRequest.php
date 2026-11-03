<?php

namespace Fabricate\Schema\V20260728\Messaging\Tasks;

use Fabricate\Contracts\Schema\Messaging\ClientRequest;
use Fabricate\Contracts\Schema\Messaging\Tasks\CancelTaskRequest as CancelTaskContract;
use Fabricate\Contracts\Schema\RequestId;
use Fabricate\Schema\Common\Messaging\RPC\JSONRPCRequest;
use Fabricate\Schema\V20260728\Messaging\Params\TaskRequestParams;

final readonly class CancelTaskRequest extends JSONRPCRequest implements CancelTaskContract, ClientRequest
{
    public function __construct(
        RequestId $id,
        TaskRequestParams $params,
    ) {
        parent::__construct("2.0", $id, "tasks/cancel", $params);
    }

    public function taskId(): string
    {
        return $this->params->taskId;
    }
}
