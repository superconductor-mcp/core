<?php

namespace Fabricate\Schema\V20260728\Capabilities\Prompts;

use Fabricate\Contracts\Schema\Capabilities\PromptDefinition as PromptDefinitionContract;
use Fabricate\Schema\Common\Concerns\BaseMetaData;
use Fabricate\Schema\Common\Concerns\Icon;
use Fabricate\Schema\V20260728\MetaObject;

readonly class PromptDefinition extends BaseMetaData implements PromptDefinitionContract
{
    /**
     * @param string $name
     * @param string|null $description
     * @param PromptArgument[]|null $arguments
     * @param Icon[]|null $icons
     * @param string|null $title
     * @param MetaObject|null $_meta
     */
    public function __construct(
        string $name,
        public ?string $description = null,
        public ?array $arguments = null,
        public ?array $icons = null,
        ?string $title = null,
        public ?MetaObject $_meta = null,
    ) {
        parent::__construct($name, $title);
    }

    public function toArray(): array
    {
        $results = parent::toArray();

        if(isset($this->description)) $results['description'] = $this->description;
        if(isset($this->arguments)) $results['arguments'] = array_map(fn(PromptArgument $argument) => $argument->toArray(), $this->arguments);
        if(isset($this->icons)) $results['icons'] = array_map(fn(Icon $icon) => $icon->toArray(), $this->icons);
        if(isset($this->_meta)) $results['_meta'] = $this->_meta->toArray() ?: (object) [];

        return $results;
    }
}
