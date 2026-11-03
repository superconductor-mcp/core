<?php

namespace Fabricate\Schema\V20260728\Messaging\Params;

use Fabricate\Schema\V20260728\Messaging\Inputs\InputResponses;
use Fabricate\Schema\V20260728\Messaging\MetaObjects\RequestMetaObject;

readonly class InputResponseRequestParams extends RequestParams
{
    public function __construct(
        RequestMetaObject $_meta,
        public ?InputResponses $inputResponses = null,
        public ?string $requestState = null,
    ) {
        parent::__construct($_meta);
    }

    public function toArray(): array
    {
        $results = parent::toArray();

        if(isset($this->inputResponses)) $results['inputResponses'] = $this->inputResponses->toArray() ?: (object) [];
        if(isset($this->requestState)) $results['requestState'] = $this->requestState;

        return $results;
    }
}
