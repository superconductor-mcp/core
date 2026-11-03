<?php

namespace Fabricate\Schema\V20260728\Concerns;

use Fabricate\Schema\Common\Concerns\BaseMetaData;

readonly class PromptReference extends BaseMetaData
{
    public string $type;

    public function __construct(
        string $name,
        ?string $title = null,
    ) {
        parent::__construct($name, $title);

        $this->type = 'ref/prompt';
    }

    public function toArray(): array
    {
        $results = parent::toArray();

        $results['type'] = $this->type;

        return $results;
    }
}
