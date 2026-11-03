<?php

namespace Fabricate\Contracts\Schema\Messaging\Tasks;

use Fabricate\Contracts\Schema\Messaging\Request;
use Fabricate\Schema\V20260728\Messaging\Inputs\InputResponses;

interface UpdateTaskRequest extends Request
{
    /** The id of the task the request is about. */
    public function taskId(): string;

    /** Responses to the task's outstanding inputRequests, by key. */
    public function inputResponses(): InputResponses;
}
