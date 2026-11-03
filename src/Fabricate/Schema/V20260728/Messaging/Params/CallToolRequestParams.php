<?php

namespace Fabricate\Schema\V20260728\Messaging\Params;

use Fabricate\Schema\V20260728\Messaging\Inputs\InputResponses;
use Fabricate\Schema\V20260728\Messaging\MetaObjects\RequestMetaObject;

final readonly class CallToolRequestParams extends InputResponseRequestParams
{
    /**
     * @param array<string,mixed>|null $arguments
     */
    public function __construct(
        public string $name,
        RequestMetaObject $_meta,
        public ?array $arguments = null,
        ?InputResponses $inputResponses = null,
        ?string $requestState = null,
    ) {
        parent::__construct($_meta, $inputResponses, $requestState);
    }

    public function toArray(): array
    {
        $results = parent::toArray();

        $results['name'] = $this->name;
        if(isset($this->arguments)) $results['arguments'] = $this->arguments ?: (object) [];

        return $results;
    }
}
