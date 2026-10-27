<?php

namespace Fabricate\NutsAndBolts\Schema;

use Fabricate\Contracts\NutsAndBolts\SchemaProperty;

class BooleanProperty implements SchemaProperty
{
    public function __construct(
        public ?string $description = null,
        public ?string $title = null,
        public ?bool $default = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'type' => 'boolean',
            'title' => $this->title,
            'description' => $this->description,
            'default' => $this->default,
        ], fn (mixed $value): bool => ! is_null($value));
    }

    public function problem(mixed $value): ?string
    {
        return is_bool($value) ? null : 'must be true or false.';
    }
}
