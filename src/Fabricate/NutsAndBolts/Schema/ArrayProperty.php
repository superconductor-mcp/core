<?php

namespace Fabricate\NutsAndBolts\Schema;

use InvalidArgumentException;
use Fabricate\Contracts\NutsAndBolts\SchemaProperty;

// a list whose every item fits one property; with an EnumProperty for items it is the multi-select an elicitation form offers
class ArrayProperty implements SchemaProperty
{
    /** @param list<mixed>|null $default 2025-11-25: prefilled items; it must fit this property's own items and counts */
    public function __construct(
        public SchemaProperty $items,
        public ?string $description = null,
        public ?string $title = null,
        public ?int $min_items = null,
        public ?int $max_items = null,
        public ?array $default = null,
    ) {
        if (($min_items ?? 0) < 0 || ($max_items ?? 0) < 0) {
            throw new InvalidArgumentException('min_items and max_items must not be negative.');
        }

        if (! is_null($min_items) && ! is_null($max_items) && $min_items > $max_items) {
            throw new InvalidArgumentException('min_items must not be above max_items.');
        }

        if (! is_null($default) && ! is_null($problem = $this->problem($default))) {
            throw new InvalidArgumentException("default {$problem}");
        }
    }

    public function toArray(): array
    {
        return array_filter([
            'type' => 'array',
            'title' => $this->title,
            'description' => $this->description,
            'minItems' => $this->min_items,
            'maxItems' => $this->max_items,
            'items' => $this->items->toArray(),
            'default' => $this->default,
        ], fn (mixed $value): bool => ! is_null($value));
    }

    public function problem(mixed $value): ?string
    {
        if (! is_array($value) || ! array_is_list($value)) {
            return 'must be a list.';
        }

        $count = count($value);

        return match (true) {
            ! is_null($this->min_items) && $count < $this->min_items => "must have at least {$this->min_items} items.",
            ! is_null($this->max_items) && $count > $this->max_items => "must have at most {$this->max_items} items.",
            default => $this->itemProblem($value),
        };
    }

    private function itemProblem(array $value): ?string
    {
        foreach ($value as $index => $item) {
            if (! is_null($problem = $this->items->problem($item))) {
                return "item {$index} {$problem}";
            }
        }

        return null;
    }
}
