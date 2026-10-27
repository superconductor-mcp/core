<?php

namespace Fabricate\NutsAndBolts\Schema;

use Fabricate\Contracts\NutsAndBolts\SchemaProperty;

// a list whose every item fits one property
class ArrayProperty implements SchemaProperty
{
    public function __construct(
        public SchemaProperty $items,
        public ?string $description = null,
        public ?string $title = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'type' => 'array',
            'title' => $this->title,
            'description' => $this->description,
            'items' => $this->items->toArray(),
        ], fn (mixed $value): bool => ! is_null($value));
    }

    public function problem(mixed $value): ?string
    {
        if (! is_array($value) || ! array_is_list($value)) {
            return 'must be a list.';
        }

        foreach ($value as $index => $item) {
            if (! is_null($problem = $this->items->problem($item))) {
                return "item {$index} {$problem}";
            }
        }

        return null;
    }
}
