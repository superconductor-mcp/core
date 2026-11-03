<?php

namespace Fabricate\Contracts\Schema\Contracts;

use Voyager\Contracts\NutsAndBolts\Arrayable;

interface Implementation extends Arrayable
{
    public function version(): string;
}
