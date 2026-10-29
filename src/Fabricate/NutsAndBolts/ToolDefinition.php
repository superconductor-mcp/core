<?php

namespace Fabricate\NutsAndBolts;

use Closure;
use InvalidArgumentException;
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
        /** @var list<Icon> 2025-11-25: images a client may show; an array entry is read as {src, mimeType?, sizes?, theme?} */
        public array $icons = [],
    ) {
        // 2025-11-25 (SEP-986): 1 to 128 characters of letters, digits, underscore, dash and dot; no spaces or commas
        if (! preg_match('/\A[A-Za-z0-9_.-]{1,128}\z/', $name)) {
            throw new InvalidArgumentException("tool name [{$name}] must be 1 to 128 characters of A-Z, a-z, 0-9, underscore, dash and dot (MCP 2025-11-25); no spaces, commas or other characters.");
        }

        Meta::check($meta);
        $this->icons = Icon::list($icons);
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
            'icons' => Icon::render($this->icons),
            'inputSchema' => $this->inputSchema()->toArray(),
            'outputSchema' => $this->output_schema?->toArray(),
            // 2025-03-26: a display name and behaviour hints; a 2024-11-05 client ignores keys it does not know
            'annotations' => $this->annotations?->toArray(),
        ], fn (mixed $value): bool => ! is_null($value)), $this->meta);
    }
}
