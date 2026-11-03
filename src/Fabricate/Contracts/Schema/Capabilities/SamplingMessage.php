<?php

namespace Fabricate\Contracts\Schema\Capabilities;

use Fabricate\Contracts\Schema\Concerns\Role;
use Voyager\Contracts\NutsAndBolts\Arrayable;

interface SamplingMessage extends Arrayable
{
    public function role(): Role;

    public function content(): mixed;
}
