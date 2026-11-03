<?php

namespace Fabricate\Schema\V20251125\Messaging\Tasks;

use Fabricate\Contracts\Schema\Messaging\Tasks\CancelTaskRequest as CancelTaskRequestContract;
use Fabricate\Contracts\Schema\Messaging\ClientRequest;
use Fabricate\Contracts\Schema\Messaging\ServerRequest;
use Fabricate\Contracts\Schema\RequestId;
use Fabricate\Schema\Common\Messaging\RPC\JSONRPCRequest;

final readonly class CancelTaskRequest extends JSONRPCRequest implements CancelTaskRequestContract, ClientRequest, ServerRequest
{
    public function __construct(
        RequestId $id,
        string $task_id,
    ) {
        parent::__construct("2.0", $id, 'tasks/cancel', ['taskId' => $task_id]);
    }

    public function taskId(): string
    {
        return $this->params['taskId'];
    }
}
