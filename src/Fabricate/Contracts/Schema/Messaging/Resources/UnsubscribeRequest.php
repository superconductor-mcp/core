<?php

namespace Fabricate\Contracts\Schema\Messaging\Resources;

use Fabricate\Contracts\Schema\Messaging\Request;

interface UnsubscribeRequest extends Request
{
    /** The URI of the resource the request is about. */
    public function uri(): string;
}
