<?php

namespace Fabricate\NutsAndBolts\Schema;

use Voyager\Contracts\NutsAndBolts\Arrayable;
use Fabricate\Contracts\Mechanism\SchemaPrimitive;

class TextProperty implements Arrayable, SchemaPrimitive
{
    public function __construct(
        public string $value
    ) {}

    // one text item of a tool result's content
    public function toArray(): array
    {
        return [
            'type' => 'text',
            'text' => $this->value,
        ];
    }
}
