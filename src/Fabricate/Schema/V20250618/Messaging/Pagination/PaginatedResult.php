<?php

namespace Fabricate\Schema\V20250618\Messaging\Pagination;

use Fabricate\Contracts\Schema\Cursor;
use Fabricate\Contracts\Schema\Messaging\Pagination\PaginatedResult as PaginatedContract;
use Fabricate\Schema\V20250618\Messaging\Result;

abstract readonly class PaginatedResult extends Result implements PaginatedContract
{
    public function __construct(
        public ?Cursor $nextCursor = null,
        ?array $_meta = null
    ) {
        parent::__construct($_meta);
    }

    public function toArray(): array
    {
        $results = parent::toArray();

        if(isset($this->nextCursor)) $results['nextCursor'] = $this->nextCursor->pointer;

        return $results;
    }
}
