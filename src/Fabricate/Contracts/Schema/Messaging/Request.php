<?php

namespace Fabricate\Contracts\Schema\Messaging;

use Voyager\Contracts\NutsAndBolts\Arrayable;

interface Request extends Arrayable
{
    public string $method { get; }
}
