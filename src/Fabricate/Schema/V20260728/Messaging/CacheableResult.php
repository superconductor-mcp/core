<?php

namespace Fabricate\Schema\V20260728\Messaging;

use Fabricate\Schema\V20260728\Concerns\CacheScope;
use Fabricate\Schema\V20260728\Concerns\ResultTypeEnum;
use Fabricate\Schema\V20260728\Messaging\MetaObjects\ResultMetaObject;

abstract readonly class CacheableResult extends Result
{
    public function __construct(
        public int $ttlMs,
        public CacheScope $cacheScope,
        string|ResultTypeEnum $resultType = ResultTypeEnum::COMPLETE,
        ?ResultMetaObject $_meta = null,
    ) {
        parent::__construct($resultType, $_meta);
    }

    public function toArray(): array
    {
        $results = parent::toArray();

        $results['ttlMs'] = $this->ttlMs;
        $results['cacheScope'] = $this->cacheScope->value;

        return $results;
    }
}
