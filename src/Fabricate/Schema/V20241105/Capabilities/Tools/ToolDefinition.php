<?php

namespace Fabricate\Schema\V20241105\Capabilities\Tools;

use Fabricate\Contracts\Schema\Capabilities\ToolDefinition as ToolDefinitionContract;

readonly class ToolDefinition implements ToolDefinitionContract
{
    public array $inputSchema;

    /**
     * @param array<string,array>|null $properties
     * @param string[]|null $required
     */
    public function __construct(
        public string $name,
        public ?string $description = null,
        ?array $properties = null,
        ?array $required = null,
    ) {
        $schema = ['type' => 'object'];
        if(isset($properties)) $schema['properties'] = array_map(fn(array $property) => $property ?: (object) [], $properties) ?: (object) [];
        if(isset($required)) $schema['required'] = $required;

        $this->inputSchema = $schema;
    }

    public function toArray(): array
    {
        $results = ['name' => $this->name];

        if(isset($this->description)) $results['description'] = $this->description;
        $results['inputSchema'] = $this->inputSchema;

        return $results;
    }
}
