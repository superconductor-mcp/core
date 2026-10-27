<?php

namespace Fabricate\NutsAndBolts;

use Closure;
use Fabricate\Contracts\NutsAndBolts\ToolDefinition as DefinitionContract;
use Voyager\Contracts\NutsAndBolts\Arrayable;

class ToolDefinition implements DefinitionContract
{

    public function __construct(
        public string $name,
        public ?string $description = null,
        public array $properties = [],
        public array $required = [],
        public Closure|string|null $action = null,
        public ?ToolAnnotations $annotations = null,
    ) {}

    public function toArray(): array
    {
        $schema = [
            'type' => 'object',
            // a JSON object even with no parameters; a PHP [] would go out as a JSON array.
            // each parameter is a schema DTO (e.g. StringProperty) or an already-built schema array
            'properties' => (object) array_map(
                fn (Arrayable|array $property): array => $property instanceof Arrayable ? $property->toArray() : $property,
                $this->properties,
            ),
        ];

        if ($this->required !== []) {
            $schema['required'] = $this->required;
        }

        return array_filter([
            'name' => $this->name,
            'description' => $this->description,
            'inputSchema' => $schema,
            // 2025-03-26: a display name and behaviour hints; a 2024-11-05 client ignores keys it does not know
            'annotations' => $this->annotations?->toArray(),
        ], fn (mixed $value): bool => ! is_null($value));
    }
}
