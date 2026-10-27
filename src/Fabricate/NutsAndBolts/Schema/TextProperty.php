<?php

namespace Fabricate\NutsAndBolts\Schema;

use Fabricate\NutsAndBolts\Meta;
use Voyager\Contracts\NutsAndBolts\Arrayable;
use Fabricate\Contracts\Mechanism\SchemaPrimitive;

class TextProperty implements Arrayable, SchemaPrimitive
{
    public function __construct(
        public string $value,
        public ?Annotations $annotations = null,
        public array $meta = [],
    ) {
        Meta::check($meta);
    }

    // one text item of a tool result's content
    public function toArray(): array
    {
        return Meta::attach(array_filter([
            'type' => 'text',
            'text' => $this->value,
            'annotations' => $this->annotations?->toArray(),
        ], fn (mixed $value): bool => ! is_null($value)), $this->meta);
    }
}
