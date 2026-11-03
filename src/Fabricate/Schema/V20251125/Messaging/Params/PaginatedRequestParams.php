<?php

namespace Fabricate\Schema\V20251125\Messaging\Params;

use Fabricate\Contracts\Schema\Cursor;
use Fabricate\Contracts\Schema\ProgressToken;

readonly class PaginatedRequestParams extends RequestParams
{
    public function __construct(
        public ?Cursor $cursor = null,
        ?array $_meta = null,
        ?ProgressToken $progress_token = null
    ) {
        parent::__construct($_meta, $progress_token);
    }

    public function toArray(): array
    {
        $results = parent::toArray();

        if(isset($this->cursor)) $results['cursor'] = $this->cursor->pointer;

        return $results;
    }
}
