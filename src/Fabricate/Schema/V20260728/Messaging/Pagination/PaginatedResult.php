<?php

namespace Fabricate\Schema\V20260728\Messaging\Pagination;

use Fabricate\Contracts\Schema\Cursor;
use Fabricate\Contracts\Schema\Messaging\Pagination\PaginatedResult as PaginatedContract;
use Fabricate\Schema\V20260728\Concerns\CacheScope;
use Fabricate\Schema\V20260728\Concerns\ResultTypeEnum;
use Fabricate\Schema\V20260728\Messaging\CacheableResult;
use Fabricate\Schema\V20260728\Messaging\MetaObjects\ResultMetaObject;

// Every paginated result in this version is also a CacheableResult, and PHP has one parent.
abstract readonly class PaginatedResult extends CacheableResult implements PaginatedContract
{
    public function __construct(
        int $ttlMs,
        CacheScope $cacheScope,
        public ?Cursor $nextCursor = null,
        string|ResultTypeEnum $resultType = ResultTypeEnum::COMPLETE,
        ?ResultMetaObject $_meta = null
    ) {
        parent::__construct($ttlMs, $cacheScope, $resultType, $_meta);
    }

    public function toArray(): array
    {
        $results = parent::toArray();

        if(isset($this->nextCursor)) $results['nextCursor'] = $this->nextCursor->pointer;

        return $results;
    }
}
