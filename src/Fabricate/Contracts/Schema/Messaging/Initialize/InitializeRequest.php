<?php

namespace Fabricate\Contracts\Schema\Messaging\Initialize;

use Fabricate\Contracts\Schema\Messaging\Request;

interface InitializeRequest extends Request
{
    public function protocolVersion(): string;
}
