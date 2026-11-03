<?php

namespace Fabricate\Schema\V20250326\Capabilities\Prompts;

use Voyager\Contracts\NutsAndBolts\Arrayable;

readonly class PromptArgument implements Arrayable
{
    public function __construct(
        public string $name,
        public ?string $description = null,
        public ?bool $required = null,
    ) {}

    public function toArray(): array
    {
        $results = ['name' => $this->name];

        if(isset($this->description)) $results['description'] = $this->description;
        if(isset($this->required)) $results['required'] = $this->required;

        return $results;
    }
}
