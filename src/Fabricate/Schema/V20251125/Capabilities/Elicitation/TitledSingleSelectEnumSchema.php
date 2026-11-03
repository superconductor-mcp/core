<?php

namespace Fabricate\Schema\V20251125\Capabilities\Elicitation;

use Fabricate\Schema\V20251125\Concerns\SingleSelectEnumSchema;

readonly class TitledSingleSelectEnumSchema implements SingleSelectEnumSchema
{
    public string $type;

    /**
     * @param array{const: string, title: string}[] $oneOf
     */
    public function __construct(
        public array $oneOf,
        public ?string $title = null,
        public ?string $description = null,
        public ?string $default = null,
    ) {
        $this->type = 'string';
    }

    public function toArray(): array
    {
        $results = [
            'type' => $this->type,
            'oneOf' => $this->oneOf,
        ];

        if(isset($this->title)) $results['title'] = $this->title;
        if(isset($this->description)) $results['description'] = $this->description;
        if(isset($this->default)) $results['default'] = $this->default;

        return $results;
    }
}
