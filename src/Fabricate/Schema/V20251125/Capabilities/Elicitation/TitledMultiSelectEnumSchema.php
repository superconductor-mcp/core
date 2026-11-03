<?php

namespace Fabricate\Schema\V20251125\Capabilities\Elicitation;

use Fabricate\Schema\V20251125\Concerns\MultiSelectEnumSchema;

readonly class TitledMultiSelectEnumSchema implements MultiSelectEnumSchema
{
    public string $type;
    public array $items;

    /**
     * @param array{const: string, title: string}[] $any_of
     * @param string[]|null $default
     */
    public function __construct(
        array $any_of,
        public ?string $title = null,
        public ?string $description = null,
        public ?int $minItems = null,
        public ?int $maxItems = null,
        public ?array $default = null,
    ) {
        $this->type = 'array';
        $this->items = ['anyOf' => $any_of];
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
