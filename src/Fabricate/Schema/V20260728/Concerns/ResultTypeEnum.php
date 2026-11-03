<?php

namespace Fabricate\Schema\V20260728\Concerns;

enum ResultTypeEnum: string
{
    case COMPLETE = 'complete';
    case INPUT_REQUIRED = 'input_required';
    // Added by the io.modelcontextprotocol/tasks extension, for CreateTaskResult.
    case TASK = 'task';
}
