<?php

namespace Fabricate\Schema\V20260728\Messaging\Params;

use Fabricate\Contracts\Schema\Cursor;
use Fabricate\Schema\V20260728\Messaging\MetaObjects\RequestMetaObject;

readonly class PaginatedRequestParams extends RequestParams
{
    public function __construct(
        RequestMetaObject $_meta,
        public ?Cursor $cursor = null,
    ) {
        parent::__construct($_meta);
    }

    public function toArray(): array
    {
        $results = parent::toArray();

        if(isset($this->cursor)) $results['cursor'] = $this->cursor->pointer;

        return $results;
    }
}
