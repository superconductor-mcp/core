<?php

namespace Fabricate\Schema\V20241105\Capabilities\Prompts;

use Fabricate\Contracts\Schema\Capabilities\PromptDefinition as PromptDefinitionContract;

readonly class PromptDefinition implements PromptDefinitionContract
{
    /**
     * @param string $name
     * @param string|null $description
     * @param PromptArgument[]|null $arguments
     */
    public function __construct(
        public string $name,
        public ?string $description = null,
        public ?array $arguments = null,
    ) {}

    public function toArray(): array
    {
        $results = ['name' => $this->name];

        if(isset($this->description)) $results['description'] = $this->description;
        if(isset($this->arguments)) $results['arguments'] = array_map(fn(PromptArgument $argument) => $argument->toArray(), $this->arguments);

        return $results;
    }
}
