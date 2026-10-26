<?php

namespace Fabricate\NutsAndBolts\Schema;

use Voyager\Contracts\NutsAndBolts\Arrayable;

class StringProperty implements Arrayable
{
    public function __construct(
        public string $description
    ) {}

    // the JSON Schema for one string parameter of a tool's inputSchema
    public function toArray(): array
    {
        return [
            'type' => 'string',
            'description' => $this->description,
        ];
    }
}
