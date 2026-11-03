<?php

namespace Fabricate\Schema\V20251125\Capabilities\Elicitation;

use Fabricate\Schema\V20251125\Concerns\MultiSelectEnumSchema;

readonly class UntitledMultiSelectEnumSchema implements MultiSelectEnumSchema
{
    public string $type;
    public array $items;

    /**
     * @param string[] $enum
     * @param string[]|null $default
     */
    public function __construct(
        array $enum,
        public ?string $title = null,
        public ?string $description = null,
        public ?int $minItems = null,
        public ?int $maxItems = null,
        public ?array $default = null,
    ) {
        $this->type = 'array';
        $this->items = ['type' => 'string', 'enum' => $enum];
    }

    public function toArray(): array
    {
        $results = [
            'type' => $this->type,
            'items' => $this->items,
        ];

        if(isset($this->title)) $results['title'] = $this->title;
        if(isset($this->description)) $results['description'] = $this->description;
        if(isset($this->minItems)) $results['minItems'] = $this->minItems;
        if(isset($this->maxItems)) $results['maxItems'] = $this->maxItems;
        if(isset($this->default)) $results['default'] = $this->default;

        return $results;
    }
}
