<?php

namespace Fabricate\Schema\V20251125\Capabilities\Tools;

use Fabricate\Contracts\Schema\Capabilities\ToolDefinition as ToolDefinitionContract;
use Fabricate\Schema\Common\Concerns\BaseMetaData;
use Fabricate\Schema\Common\Concerns\Icon;

readonly class ToolDefinition extends BaseMetaData implements ToolDefinitionContract
{
    public array $inputSchema;
    public ?array $outputSchema;

    /**
     * @param array<string,array>|null $properties
     * @param string[]|null $required
     * @param array<string,array>|null $output_properties
     * @param string[]|null $output_required
     * @param Icon[]|null $icons
     */
    public function __construct(
        string $name,
        public ?string $description = null,
        ?array $properties = null,
        ?array $required = null,
        ?string $schema_dialect = null,
        bool $has_output_schema = false,
        ?array $output_properties = null,
        ?array $output_required = null,
        ?string $output_schema_dialect = null,
        public ?ToolExecution $execution = null,
        public ?ToolAnnotations $annotations = null,
        public ?array $icons = null,
        ?string $title = null,
        public ?array $_meta = null,
    ) {
        parent::__construct($name, $title);

        $schema = ['type' => 'object'];
        if(isset($schema_dialect)) $schema['$schema'] = $schema_dialect;
        if(isset($properties)) $schema['properties'] = array_map(fn(array $property) => $property ?: (object) [], $properties) ?: (object) [];
        if(isset($required)) $schema['required'] = $required;

        $this->inputSchema = $schema;

        if($has_output_schema)
        {
            $schema = ['type' => 'object'];
            if(isset($output_schema_dialect)) $schema['$schema'] = $output_schema_dialect;
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
        if(isset($this->execution)) $results['execution'] = $this->execution->toArray() ?: (object) [];
        if(isset($this->outputSchema)) $results['outputSchema'] = $this->outputSchema;
        if(isset($this->annotations)) $results['annotations'] = $this->annotations->toArray() ?: (object) [];
        if(isset($this->icons)) $results['icons'] = array_map(fn(Icon $icon) => $icon->toArray(), $this->icons);
        if(isset($this->_meta)) $results['_meta'] = $this->_meta ?: (object) [];

        return $results;
    }
}
