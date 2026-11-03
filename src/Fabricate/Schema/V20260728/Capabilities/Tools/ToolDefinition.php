<?php

namespace Fabricate\Schema\V20260728\Capabilities\Tools;

use Fabricate\Contracts\Schema\Capabilities\ToolDefinition as ToolDefinitionContract;
use Fabricate\Schema\Common\Concerns\BaseMetaData;
use Fabricate\Schema\Common\Concerns\Icon;
use Fabricate\Schema\V20260728\MetaObject;

readonly class ToolDefinition extends BaseMetaData implements ToolDefinitionContract
{
    public array $inputSchema;
    public array|object|null $outputSchema;

    /**
     * Both schemas are open JSON Schema objects in this version, so any keyword may be passed through.
     *
     * @param array<string,mixed> $input_schema
     * @param array<string,mixed>|null $output_schema
     * @param Icon[]|null $icons
     */
    public function __construct(
        string $name,
        public ?string $description = null,
        array $input_schema = [],
        ?array $output_schema = null,
        public ?ToolAnnotations $annotations = null,
        public ?array $icons = null,
        ?string $title = null,
        public ?MetaObject $_meta = null,
    ) {
        parent::__construct($name, $title);

        $input_schema['type'] = 'object';
        if(isset($input_schema['properties'])) $input_schema['properties'] = array_map(fn(array $property) => $property ?: (object) [], $input_schema['properties']) ?: (object) [];

        $this->inputSchema = $input_schema;

        if(isset($output_schema['properties'])) $output_schema['properties'] = array_map(fn(array $property) => $property ?: (object) [], $output_schema['properties']) ?: (object) [];

        $this->outputSchema = is_null($output_schema) ? null : ($output_schema ?: (object) []);
    }

    public function toArray(): array
    {
        $results = parent::toArray();

        if(isset($this->description)) $results['description'] = $this->description;
        $results['inputSchema'] = $this->inputSchema;
        if(isset($this->outputSchema)) $results['outputSchema'] = $this->outputSchema;
        if(isset($this->annotations)) $results['annotations'] = $this->annotations->toArray() ?: (object) [];
        if(isset($this->icons)) $results['icons'] = array_map(fn(Icon $icon) => $icon->toArray(), $this->icons);
        if(isset($this->_meta)) $results['_meta'] = $this->_meta->toArray() ?: (object) [];

        return $results;
    }
}
