<?php

namespace Fabricate\NutsAndBolts\Schema;

use InvalidArgumentException;
use Fabricate\Contracts\NutsAndBolts\SchemaProperty;

/**
 * A string limited to a fixed list. Names, when given, are what a form shows for each value, and go out one of three ways:
 * beside `enum` as the legacy `enumNames` (no `titled`), as `oneOf` of {const, title} (a titled single-select), or as `anyOf`
 * of the same with no `type` (the items of a titled multi-select, inside an ArrayProperty).
 */
class EnumProperty implements SchemaProperty
{
    public const string ONE_OF = 'oneOf';

    public const string ANY_OF = 'anyOf';

    /**
     * @param list<string> $values
     * @param list<string>|null $names
     * @param string|null $default 2025-11-25: prefilled; one of the values
     */
    public function __construct(
        public array $values,
        public ?array $names = null,
        public ?string $description = null,
        public ?string $title = null,
        public ?string $titled = null,
        public ?string $default = null,
    ) {
        if ($values === [] || ! array_is_list($values) || array_filter($values, 'is_string') !== $values) {
            throw new InvalidArgumentException('values must be a list of strings.');
        }

        if (! is_null($names) && (! array_is_list($names) || count($names) !== count($values) || array_filter($names, 'is_string') !== $names)) {
            throw new InvalidArgumentException('names must be strings, one per value.');
        }

        if (! is_null($titled) && (! in_array($titled, [self::ONE_OF, self::ANY_OF], true) || is_null($names))) {
            throw new InvalidArgumentException('titled must be oneOf or anyOf, and needs a name for each value.');
        }

        if (! is_null($default) && ! is_null($problem = $this->problem($default))) {
            throw new InvalidArgumentException("default {$problem}");
        }
    }

    public function toArray(): array
    {
        $options = is_null($this->titled) ? null : array_map(
            fn (string $value, string $name): array => ['const' => $value, 'title' => $name],
            $this->values,
            $this->names,
        );

        return array_filter([
            'type' => $this->titled === self::ANY_OF ? null : 'string',
            'title' => $this->title,
            'description' => $this->description,
            'enum' => is_null($this->titled) ? $this->values : null,
            'enumNames' => is_null($this->titled) ? $this->names : null,
            $this->titled ?? 'titled' => $options,
            'default' => $this->default,
        ], fn (mixed $value): bool => ! is_null($value));
    }

    public function problem(mixed $value): ?string
    {
        return is_string($value) && in_array($value, $this->values, true) ? null : 'must be one of: '.implode(', ', $this->values).'.';
    }
}
