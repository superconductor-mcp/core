<?php

namespace Fabricate\Schema\V20251125\Concerns;

enum TaskSupport: string
{
    case FORBIDDEN = 'forbidden';
    case OPTIONAL = 'optional';
    case REQUIRED = 'required';
}
