<?php

namespace Fabricate\Contracts\Schema\Messaging\Tasks;

use Fabricate\Contracts\Schema\Messaging\Request;

interface GetTaskPayloadRequest extends Request
{
    /** The id of the task the request is about. */
    public function taskId(): string;
}
