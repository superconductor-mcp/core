<?php

namespace Fabricate\Schema\V20260728\Capabilities\Elicitation;

use Fabricate\Schema\V20260728\Concerns\SingleSelectEnumSchema;

readonly class UntitledSingleSelectEnumSchema implements SingleSelectEnumSchema
{
    public string $type;

    /**
     * @param string[] $enum
     */
    public function __construct(
        public array $enum,
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
            'enum' => $this->enum,
        ];

        if(isset($this->title)) $results['title'] = $this->title;
        if(isset($this->description)) $results['description'] = $this->description;
        if(isset($this->default)) $results['default'] = $this->default;

        return $results;
    }
}
