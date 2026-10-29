<?php

declare(strict_types=1);

namespace Fabricate\NutsAndBolts\Schema;

use TypeError;
use InvalidArgumentException;
use Fabricate\Contracts\NutsAndBolts\SchemaProperty;

/**
 * An object of named properties: a tool's inputSchema and outputSchema, an elicitation form, or a nested property.
 * problem() refuses keys the schema does not name.
 */
class ObjectSchema implements SchemaProperty
{
    // the keywords each flat property may carry in an elicitation form
    private const array FLAT_KEYS = [
        'string' => ['type', 'title', 'description', 'minLength', 'maxLength', 'format', 'default'],
        'enum' => ['type', 'title', 'description', 'enum', 'enumNames', 'oneOf', 'default'],
        'number' => ['type', 'title', 'description', 'minimum', 'maximum', 'default'],
        'integer' => ['type', 'title', 'description', 'minimum', 'maximum', 'default'],
        'boolean' => ['type', 'title', 'description', 'default'],
        'multi' => ['type', 'title', 'description', 'minItems', 'maxItems', 'items', 'default'],
    ];

    /**
     * @param array<string, SchemaProperty> $properties
     * @param list<string> $required
     */
    public function __construct(
        public array $properties = [],
        public array $required = [],
        public ?string $description = null,
        public ?string $title = null,
    ) {
        foreach ($properties as $name => $property) {
            if (! $property instanceof SchemaProperty) {
                throw new InvalidArgumentException("property [{$name}] must be a SchemaProperty.");
            }
        }

        if (! array_is_list($required) || array_diff($required, array_keys($properties)) !== []) {
            throw new InvalidArgumentException('required must list property names the schema defines.');
        }
    }

    /**
     * Reads the flat subset 2025-11-25 allows in elicitation/create: string, number, integer, boolean, single-select
     * (`enum`, `oneOf`, legacy `enumNames`) and multi-select (an array of strings from an `enum` or an `anyOf`) properties only. Anything else, including a keyword outside that subset, is refused.
     *
     * @throws InvalidArgumentException naming what is wrong
     */
    public static function fromArray(array $schema): self
    {
        if (array_diff(array_keys($schema), ['type', 'properties', 'required']) !== [] || ($schema['type'] ?? null) !== 'object') {
            throw new InvalidArgumentException('the schema must be an object schema with only type, properties and required.');
        }

        $properties = $schema['properties'] ?? [];
        $required = $schema['required'] ?? [];

        if (! is_array($properties) || ($properties !== [] && array_is_list($properties))) {
            throw new InvalidArgumentException('properties must be an object.');
        }

        if (! is_array($required)) {
            throw new InvalidArgumentException('required must list property names the schema defines.');
        }

        $read = [];

        foreach ($properties as $name => $property) {
            $read[$name] = self::flatProperty((string) $name, $property);
        }

        return new self($read, $required);
    }

    // only string, number, integer, boolean, enum and multi-select properties: the shape elicitation allows
    public function isFlat(): bool
    {
        foreach ($this->properties as $property) {
            if (! $property instanceof StringProperty && ! $property instanceof NumberProperty && ! $property instanceof BooleanProperty && ! $property instanceof EnumProperty && ! self::isMultiSelect($property)) {
                return false;
            }
        }

        return true;
    }

    // an array whose items are bare string options: `enum` (untitled) or `anyOf` (titled), nothing else on the items
    private static function isMultiSelect(SchemaProperty $property): bool
    {
        return $property instanceof ArrayProperty
            && $property->items instanceof EnumProperty
            && is_null($property->items->title)
            && is_null($property->items->description)
            && is_null($property->items->default)
            && $property->items->titled !== EnumProperty::ONE_OF
            && ($property->items->titled === EnumProperty::ANY_OF || is_null($property->items->names));
    }

    public function toArray(): array
    {
        return array_filter([
            'type' => 'object',
            'title' => $this->title,
            'description' => $this->description,
            // a JSON object even with no properties; a PHP [] would go out as a JSON array
            'properties' => (object) array_map(fn (SchemaProperty $property): array => $property->toArray(), $this->properties),
            'required' => $this->required === [] ? null : $this->required,
        ], fn (mixed $value): bool => ! is_null($value));
    }

    public function problem(mixed $value): ?string
    {
        if (! is_array($value) || ($value !== [] && array_is_list($value))) {
            return 'must be an object.';
        }

        foreach ($this->required as $name) {
            if (! array_key_exists($name, $value)) {
                return "[{$name}] is required.";
            }
        }

        foreach ($value as $name => $item) {
            if (! isset($this->properties[$name])) {
                return "[{$name}] is not in the schema.";
            }

            if (! is_null($problem = $this->properties[$name]->problem($item))) {
                return "[{$name}] {$problem}";
            }
        }

        return null;
    }

    private static function flatProperty(string $name, mixed $property): SchemaProperty
    {
        $kind = is_array($property) ? match ($property['type'] ?? null) {
            'string' => array_key_exists('enum', $property) || array_key_exists('oneOf', $property) ? 'enum' : 'string',
            'number', 'integer', 'boolean' => $property['type'],
            'array' => 'multi',
            default => null,
        } : null;

        if (is_null($kind)) {
            throw new InvalidArgumentException("property [{$name}] must be a string, number, integer, boolean, string enum or array of string options.");
        }

        if (($extra = array_diff(array_keys($property), self::FLAT_KEYS[$kind])) !== []) {
            throw new InvalidArgumentException("property [{$name}] uses keywords outside the elicitation subset: ".implode(', ', $extra).'.');
        }

        $description = $property['description'] ?? null;
        $title = $property['title'] ?? null;
        $default = $property['default'] ?? null;

        try {
            return match ($kind) {
                'string' => new StringProperty($description, $title, $property['minLength'] ?? null, $property['maxLength'] ?? null, $property['format'] ?? null, $default),
                'enum' => self::enumProperty($property, $description, $title, $default),
                'number' => new NumberProperty($description, $title, $property['minimum'] ?? null, $property['maximum'] ?? null, $default),
                'integer' => new IntegerProperty($description, $title, $property['minimum'] ?? null, $property['maximum'] ?? null, $default),
                'boolean' => new BooleanProperty($description, $title, $default),
                'multi' => new ArrayProperty(self::optionItems($property['items'] ?? null), $description, $title, $property['minItems'] ?? null, $property['maxItems'] ?? null, $default),
            };
        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException("property [{$name}] {$e->getMessage()}");
        } catch (TypeError) {
            throw new InvalidArgumentException("property [{$name}] has a keyword of the wrong type.");
        }
    }

    // a single-select: `enum` (with the legacy `enumNames` beside it) or `oneOf`, never both
    private static function enumProperty(array $property, ?string $description, ?string $title, mixed $default): EnumProperty
    {
        if (array_key_exists('oneOf', $property)) {
            if (array_key_exists('enum', $property) || array_key_exists('enumNames', $property)) {
                throw new InvalidArgumentException('uses oneOf, which excludes enum and enumNames.');
            }

            [$values, $names] = self::options($property['oneOf'], 'oneOf');

            return new EnumProperty($values, $names, $description, $title, EnumProperty::ONE_OF, $default);
        }

        return new EnumProperty($property['enum'], $property['enumNames'] ?? null, $description, $title, null, $default);
    }

    // the items of a multi-select: {type: string, enum} or {anyOf: [{const, title}]}, and no other keyword
    private static function optionItems(mixed $items): EnumProperty
    {
        if (is_array($items) && array_keys($items) === ['anyOf']) {
            [$values, $names] = self::options($items['anyOf'], 'anyOf');

            return new EnumProperty($values, $names, titled: EnumProperty::ANY_OF);
        }

        if (is_array($items) && ($items['type'] ?? null) === 'string' && is_array($items['enum'] ?? null) && array_diff(array_keys($items), ['type', 'enum']) === []) {
            return new EnumProperty($items['enum']);
        }

        throw new InvalidArgumentException('items must be {type: string, enum: [...]} or {anyOf: [{const, title}]}.');
    }

    /** @return array{list<string>, list<string>} the const and title of each {const, title} option */
    private static function options(mixed $options, string $keyword): array
    {
        $values = $names = [];

        foreach (is_array($options) && array_is_list($options) ? $options : [] as $option) {
            if (! is_array($option) || array_keys($option) !== ['const', 'title'] && array_keys($option) !== ['title', 'const'] || ! is_string($option['const']) || ! is_string($option['title'])) {
                throw new InvalidArgumentException("{$keyword} must be a list of {const, title} strings.");
            }

            $values[] = $option['const'];
            $names[] = $option['title'];
        }

        if ($values === []) {
            throw new InvalidArgumentException("{$keyword} must be a list of {const, title} strings.");
        }

        return [$values, $names];
    }
}
