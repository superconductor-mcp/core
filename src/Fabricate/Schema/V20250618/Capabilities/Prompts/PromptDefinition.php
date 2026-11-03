<?php

namespace Fabricate\Schema\V20250618\Capabilities\Prompts;

use Fabricate\Contracts\Schema\Capabilities\PromptDefinition as PromptDefinitionContract;
use Fabricate\Schema\Common\Concerns\BaseMetaData;

readonly class PromptDefinition extends BaseMetaData implements PromptDefinitionContract
{
    /**
     * @param string $name
     * @param string|null $description
     * @param PromptArgument[]|null $arguments
     * @param string|null $title
     * @param array|null $_meta
     */
    public function __construct(
        string $name,
        public ?string $description = null,
        public ?array $arguments = null,
        ?string $title = null,
        public ?array $_meta = null,
    ) {
        parent::__construct($name, $title);
    }

    public function toArray(): array
    {
        $results = parent::toArray();

        if(isset($this->description)) $results['description'] = $this->description;
        if(isset($this->arguments)) $results['arguments'] = array_map(fn(PromptArgument $argument) => $argument->toArray(), $this->arguments);
        if(isset($this->_meta)) $results['_meta'] = $this->_meta ?: (object) [];

        return $results;
    }
}
