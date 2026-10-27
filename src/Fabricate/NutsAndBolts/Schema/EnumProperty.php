<?php

namespace Fabricate\NutsAndBolts\Schema;

use InvalidArgumentException;
use Fabricate\Contracts\NutsAndBolts\SchemaProperty;

// a string limited to a fixed list; names, when given, are what a form shows for each value
class EnumProperty implements SchemaProperty
{
    /**
     * @param list<string> $values
     * @param list<string>|null $names
     */
    public function __construct(
        public array $values,
        public ?array $names = null,
        public ?string $description = null,
        public ?string $title = null,
    ) {
        if ($values === [] || ! array_is_list($values) || array_filter($values, 'is_string') !== $values) {
            throw new InvalidArgumentException('values must be a list of strings.');
        }

        if (! is_null($names) && (! array_is_list($names) || count($names) !== count($values) || array_filter($names, 'is_string') !== $names)) {
            throw new InvalidArgumentException('names must be strings, one per value.');
        }
    }

    public function toArray(): array
    {
        return array_filter([
            'type' => 'string',
            'title' => $this->title,
            'description' => $this->description,
            'enum' => $this->values,
            'enumNames' => $this->names,
        ], fn (mixed $value): bool => ! is_null($value));
    }

    public function problem(mixed $value): ?string
    {
        return is_string($value) && in_array($value, $this->values, true) ? null : 'must be one of: '.implode(', ', $this->values).'.';
    }
}
