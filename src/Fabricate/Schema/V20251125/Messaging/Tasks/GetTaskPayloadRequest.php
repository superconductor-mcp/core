<?php

namespace Fabricate\Schema\V20251125\Messaging\Tasks;

use Fabricate\Contracts\Schema\Messaging\Tasks\GetTaskPayloadRequest as GetTaskPayloadRequestContract;
use Fabricate\Contracts\Schema\Messaging\ClientRequest;
use Fabricate\Contracts\Schema\Messaging\ServerRequest;
use Fabricate\Contracts\Schema\RequestId;
use Fabricate\Schema\Common\Messaging\RPC\JSONRPCRequest;

final readonly class GetTaskPayloadRequest extends JSONRPCRequest implements GetTaskPayloadRequestContract, ClientRequest, ServerRequest
{
    public function __construct(
        RequestId $id,
        string $task_id,
    ) {
        parent::__construct("2.0", $id, 'tasks/result', ['taskId' => $task_id]);
    }

    public function taskId(): string
    {
        return $this->params['taskId'];
    }
}
