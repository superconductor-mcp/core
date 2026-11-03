<?php

namespace Fabricate\Contracts\Schema\Messaging\Logging;

use Fabricate\Contracts\Schema\LoggingLevel;
use Fabricate\Contracts\Schema\Messaging\Request;

interface SetLevelRequest extends Request
{
    public function level(): LoggingLevel;
}
