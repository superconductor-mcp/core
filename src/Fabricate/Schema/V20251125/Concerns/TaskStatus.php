<?php

namespace Fabricate\Schema\V20251125\Concerns;

enum TaskStatus: string
{
    case WORKING = 'working';
    case INPUT_REQUIRED = 'input_required';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
}
