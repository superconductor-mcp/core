<?php

namespace Fabricate\Schema\V20260728\Capabilities\Elicitation;

use Fabricate\Schema\V20260728\Concerns\PrimitiveSchemaDefinition;

readonly class StringSchema implements PrimitiveSchemaDefinition
{
    public string $type;

    public function __construct(
        public ?string $title = null,
        public ?string $description = null,
        public ?int $minLength = null,
        public ?int $maxLength = null,
        public ?string $format = null,
        public ?string $default = null,
    ) {
        $this->type = 'string';
    }

    public function toArray(): array
    {
        $results = ['type' => $this->type];

        if(isset($this->title)) $results['title'] = $this->title;
        if(isset($this->description)) $results['description'] = $this->description;
        if(isset($this->minLength)) $results['minLength'] = $this->minLength;
        if(isset($this->maxLength)) $results['maxLength'] = $this->maxLength;
        if(isset($this->format)) $results['format'] = $this->format;
        if(isset($this->default)) $results['default'] = $this->default;

        return $results;
    }
}
