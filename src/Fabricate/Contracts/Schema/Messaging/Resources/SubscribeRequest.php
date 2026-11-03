<?php

namespace Fabricate\Contracts\Schema\Messaging\Resources;

use Fabricate\Contracts\Schema\Messaging\Request;

interface SubscribeRequest extends Request
{
    /** The URI of the resource the request is about. */
    public function uri(): string;
}
