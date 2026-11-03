<?php

namespace Fabricate\Schema\V20260728\Messaging\Params;

use Fabricate\Schema\V20260728\Messaging\Inputs\InputResponses;
use Fabricate\Schema\V20260728\Messaging\MetaObjects\RequestMetaObject;

// ReadResourceRequestParams is this type's only child and also needs InputResponseRequestParams, and PHP has one parent.
abstract readonly class ResourceRequestParams extends InputResponseRequestParams
{
    public function __construct(
        public string $uri,
        RequestMetaObject $_meta,
        ?InputResponses $inputResponses = null,
        ?string $requestState = null,
    ) {
        parent::__construct($_meta, $inputResponses, $requestState);
    }

    public function toArray(): array
    {
        $results = parent::toArray();

        $results['uri'] = $this->uri;

        return $results;
    }
}
