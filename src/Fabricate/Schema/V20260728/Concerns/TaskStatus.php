<?php

namespace Fabricate\Schema\V20260728\Concerns;

/**
 * The status of a task, from the io.modelcontextprotocol/tasks extension.
 */
enum TaskStatus: string
{
    case WORKING = 'working';
    case INPUT_REQUIRED = 'input_required';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
}
