<?php

namespace Fabricate\Schema\V20250618\Capabilities\Tools;

use Fabricate\Contracts\Schema\Capabilities\ToolDefinition as ToolDefinitionContract;
use Fabricate\Schema\Common\Concerns\BaseMetaData;

readonly class ToolDefinition extends BaseMetaData implements ToolDefinitionContract
{
    public array $inputSchema;
    public ?array $outputSchema;

    /**
     * @param array<string,array>|null $properties
     * @param string[]|null $required
     * @param array<string,array>|null $output_properties
     * @param string[]|null $output_required
     */
    public function __construct(
        string $name,
        public ?string $description = null,
        ?array $properties = null,
        ?array $required = null,
        bool $has_output_schema = false,
        ?array $output_properties = null,
        ?array $output_required = null,
        public ?ToolAnnotations $annotations = null,
        ?string $title = null,
        public ?array $_meta = null,
    ) {
        parent::__construct($name, $title);

        $schema = ['type' => 'object'];
        if(isset($properties)) $schema['properties'] = array_map(fn(array $property) => $property ?: (object) [], $properties) ?: (object) [];
        if(isset($required)) $schema['required'] = $required;

        $this->inputSchema = $schema;

        if($has_output_schema)
        {
            $schema = ['type' => 'object'];
            if(isset($output_properties)) $schema['properties'] = array_map(fn(array $property) => $property ?: (object) [], $output_properties) ?: (object) [];
            if(isset($output_required)) $schema['required'] = $output_required;

            $this->outputSchema = $schema;
        }
        else $this->outputSchema = null;
    }

    public function toArray(): array
    {
        $results = parent::toArray();

        if(isset($this->description)) $results['description'] = $this->description;
        $results['inputSchema'] = $this->inputSchema;
        if(isset($this->outputSchema)) $results['outputSchema'] = $this->outputSchema;
        if(isset($this->annotations)) $results['annotations'] = $this->annotations->toArray() ?: (object) [];
        if(isset($this->_meta)) $results['_meta'] = $this->_meta ?: (object) [];

        return $results;
    }
}
