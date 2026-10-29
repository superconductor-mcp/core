<?php

namespace Fabricate\NutsAndBolts\Schema;

use InvalidArgumentException;
use Fabricate\Contracts\NutsAndBolts\SchemaProperty;

class NumberProperty implements SchemaProperty
{
    protected const string TYPE = 'number';

    public function __construct(
        public ?string $description = null,
        public ?string $title = null,
        public int|float|null $minimum = null,
        public int|float|null $maximum = null,
        // 2025-11-25: what a form shows prefilled; it must fit this property's own bounds
        public int|float|null $default = null,
    ) {
        if (! is_null($minimum) && ! is_null($maximum) && $minimum > $maximum) {
            throw new InvalidArgumentException('minimum must not be above maximum.');
        }

        if (! is_null($default) && ! is_null($problem = $this->problem($default))) {
            throw new InvalidArgumentException("default {$problem}");
        }
    }

    public function toArray(): array
    {
        return array_filter([
            'type' => static::TYPE,
            'title' => $this->title,
            'description' => $this->description,
            'minimum' => $this->minimum,
            'maximum' => $this->maximum,
            'default' => $this->default,
        ], fn (mixed $value): bool => ! is_null($value));
    }

    public function problem(mixed $value): ?string
    {
        return match (true) {
            ! is_int($value) && ! is_float($value) => 'must be a number.',
            ! is_null($this->minimum) && $value < $this->minimum => "must be at least {$this->minimum}.",
            ! is_null($this->maximum) && $value > $this->maximum => "must be at most {$this->maximum}.",
            default => null,
        };
    }
}
