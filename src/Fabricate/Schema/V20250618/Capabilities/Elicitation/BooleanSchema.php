<?php

namespace Fabricate\Schema\V20250618\Capabilities\Elicitation;

use Fabricate\Schema\V20250618\Concerns\PrimitiveSchemaDefinition;

readonly class BooleanSchema implements PrimitiveSchemaDefinition
{
    public string $type;

    public function __construct(
        public ?string $title = null,
        public ?string $description = null,
        public ?bool $default = null,
    ) {
        $this->type = 'boolean';
    }

    public function toArray(): array
    {
        $results = ['type' => $this->type];

        if(isset($this->title)) $results['title'] = $this->title;
        if(isset($this->description)) $results['description'] = $this->description;
        if(isset($this->default)) $results['default'] = $this->default;

        return $results;
    }
}
