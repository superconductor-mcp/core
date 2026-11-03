<?php

namespace Fabricate\Schema\V20250618\Capabilities\Elicitation;

use Fabricate\Schema\V20250618\Concerns\PrimitiveSchemaDefinition;

readonly class EnumSchema implements PrimitiveSchemaDefinition
{
    public string $type;

    /**
     * @param string[] $enum
     * @param string[]|null $enumNames
     */
    public function __construct(
        public array $enum,
        public ?array $enumNames = null,
        public ?string $title = null,
        public ?string $description = null,
    ) {
        $this->type = 'string';
    }

    public function toArray(): array
    {
        $results = [
            'type' => $this->type,
            'enum' => $this->enum,
        ];

        if(isset($this->enumNames)) $results['enumNames'] = $this->enumNames;
        if(isset($this->title)) $results['title'] = $this->title;
        if(isset($this->description)) $results['description'] = $this->description;

        return $results;
    }
}
