<?php

namespace Fabricate\Schema\V20260728\Capabilities\Prompts;

use Fabricate\Schema\Common\Concerns\BaseMetaData;

readonly class PromptArgument extends BaseMetaData
{
    public function __construct(
        string $name,
        public ?string $description = null,
        public ?bool $required = null,
        ?string $title = null,
    ) {
        parent::__construct($name, $title);
    }

    public function toArray(): array
    {
        $results = parent::toArray();

        if(isset($this->description)) $results['description'] = $this->description;
        if(isset($this->required)) $results['required'] = $this->required;

        return $results;
    }
}
