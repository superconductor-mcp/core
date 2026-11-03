<?php

namespace Fabricate\Schema\V20260728\Capabilities\Elicitation;

use Fabricate\Schema\V20260728\Concerns\PrimitiveSchemaDefinition;

readonly class NumberSchema implements PrimitiveSchemaDefinition
{
    public string $type;

    public function __construct(
        bool $integer = false,
        public ?string $title = null,
        public ?string $description = null,
        public int|float|null $minimum = null,
        public int|float|null $maximum = null,
        public int|float|null $default = null,
    ) {
        $this->type = $integer ? 'integer' : 'number';
    }

    public function toArray(): array
    {
        $results = ['type' => $this->type];

        if(isset($this->title)) $results['title'] = $this->title;
        if(isset($this->description)) $results['description'] = $this->description;
        if(isset($this->minimum)) $results['minimum'] = $this->minimum;
        if(isset($this->maximum)) $results['maximum'] = $this->maximum;
        if(isset($this->default)) $results['default'] = $this->default;

        return $results;
    }
}
