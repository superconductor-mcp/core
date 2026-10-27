<?php

namespace Fabricate\Contracts\NutsAndBolts;

use Voyager\Contracts\NutsAndBolts\Arrayable;

// one JSON Schema property: renders itself and checks a value against itself
interface SchemaProperty extends Arrayable
{
    // what is wrong with the value, or null when it fits
    public function problem(mixed $value): ?string;
}
