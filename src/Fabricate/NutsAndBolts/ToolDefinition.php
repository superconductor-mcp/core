<?php

namespace Fabricate\NutsAndBolts;

use Closure;
use Fabricate\NutsAndBolts\Schema\ObjectSchema;
use Fabricate\Contracts\NutsAndBolts\SchemaProperty;
use Fabricate\Contracts\NutsAndBolts\ToolDefinition as DefinitionContract;

class ToolDefinition implements DefinitionContract
{

    /**
     * @param array<string, SchemaProperty> $properties
     * @param list<string> $required
     */
    public function __construct(
        public string $name,
        public ?string $description = null,
        public array $properties = [],
        public array $required = [],
        public Closure|string|null $action = null,
        public ?ToolAnnotations $annotations = null,
        // 2025-06-18: a display name; `name` stays the identifier
        public ?string $title = null,
        // 2025-06-18: the shape of the tool's structured result; sent as declared, never checked here
        public ?ObjectSchema $output_schema = null,
        public array $meta = [],
    ) {
        Meta::check($meta);
        // a bad property or required name fails here, not when tools/list is answered
        $this->inputSchema();
    }

    public function inputSchema(): ObjectSchema
    {
        return new ObjectSchema($this->properties, $this->required);
    }

    public function toArray(): array
    {
        return Meta::attach(array_filter([
            'name' => $this->name,
            'title' => $this->title,
            'description' => $this->description,
            'inputSchema' => $this->inputSchema()->toArray(),
            'outputSchema' => $this->output_schema?->toArray(),
            // 2025-03-26: a display name and behaviour hints; a 2024-11-05 client ignores keys it does not know
            'annotations' => $this->annotations?->toArray(),
        ], fn (mixed $value): bool => ! is_null($value)), $this->meta);
    }
}
