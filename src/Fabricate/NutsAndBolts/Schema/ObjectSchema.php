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
        'string' => ['type', 'title', 'description', 'minLength', 'maxLength', 'format'],
        'enum' => ['type', 'title', 'description', 'enum', 'enumNames'],
        'number' => ['type', 'title', 'description', 'minimum', 'maximum'],
        'integer' => ['type', 'title', 'description', 'minimum', 'maximum'],
        'boolean' => ['type', 'title', 'description', 'default'],
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
     * Reads the flat subset 2025-06-18 allows in elicitation/create: string, number, integer, boolean and string-enum
     * properties only. Anything else, including a keyword outside that subset, is refused.
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

    // only string, number, integer, boolean and enum properties: the shape elicitation allows
    public function isFlat(): bool
    {
        foreach ($this->properties as $property) {
            if (! $property instanceof StringProperty && ! $property instanceof NumberProperty && ! $property instanceof BooleanProperty && ! $property instanceof EnumProperty) {
                return false;
            }
        }

        return true;
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
            'string' => array_key_exists('enum', $property) ? 'enum' : 'string',
            'number', 'integer', 'boolean' => $property['type'],
            default => null,
        } : null;

        if (is_null($kind)) {
            throw new InvalidArgumentException("property [{$name}] must be a string, number, integer, boolean or string enum.");
        }

        if (($extra = array_diff(array_keys($property), self::FLAT_KEYS[$kind])) !== []) {
            throw new InvalidArgumentException("property [{$name}] uses keywords outside the elicitation subset: ".implode(', ', $extra).'.');
        }

        $description = $property['description'] ?? null;
        $title = $property['title'] ?? null;

        try {
            return match ($kind) {
                'string' => new StringProperty($description, $title, $property['minLength'] ?? null, $property['maxLength'] ?? null, $property['format'] ?? null),
                'enum' => new EnumProperty($property['enum'], $property['enumNames'] ?? null, $description, $title),
                'number' => new NumberProperty($description, $title, $property['minimum'] ?? null, $property['maximum'] ?? null),
                'integer' => new IntegerProperty($description, $title, $property['minimum'] ?? null, $property['maximum'] ?? null),
                'boolean' => new BooleanProperty($description, $title, $property['default'] ?? null),
            };
        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException("property [{$name}] {$e->getMessage()}");
        } catch (TypeError) {
            throw new InvalidArgumentException("property [{$name}] has a keyword of the wrong type.");
        }
    }
}
