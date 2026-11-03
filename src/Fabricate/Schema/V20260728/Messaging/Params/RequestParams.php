<?php

namespace Fabricate\Schema\V20260728\Messaging\Params;

use Fabricate\Schema\V20260728\Messaging\MetaObjects\RequestMetaObject;
use Voyager\Contracts\NutsAndBolts\Arrayable;

readonly class RequestParams implements Arrayable
{
    public function __construct(
        public RequestMetaObject $_meta,
    ) {}

    public function toArray(): array
    {
        return ['_meta' => $this->_meta->toArray()];
    }
}
