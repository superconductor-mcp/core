<?php

namespace Fabricate\Schema\V20260728\Messaging\Resources;

use Fabricate\Contracts\Schema\Cursor;
use Fabricate\Contracts\Schema\Messaging\Resources\ListResourcesResult as ListResourcesContract;
use Fabricate\Contracts\Schema\Messaging\ServerResult;
use Fabricate\Schema\V20260728\Capabilities\Resources\ResourceDefinition;
use Fabricate\Schema\V20260728\Concerns\CacheScope;
use Fabricate\Schema\V20260728\Concerns\ResultTypeEnum;
use Fabricate\Schema\V20260728\Messaging\MetaObjects\ResultMetaObject;
use Fabricate\Schema\V20260728\Messaging\Pagination\PaginatedResult;

final readonly class ListResourcesResult extends PaginatedResult implements ListResourcesContract, ServerResult
{
    /**
     * @param ResourceDefinition[] $resources
     */
    public function __construct(
        public array $resources,
        int $ttlMs,
        CacheScope $cacheScope,
        ?Cursor $nextCursor = null,
        string|ResultTypeEnum $resultType = ResultTypeEnum::COMPLETE,
        ?ResultMetaObject $_meta = null
    ) {
        parent::__construct($ttlMs, $cacheScope, $nextCursor, $resultType, $_meta);
    }

    public function toArray(): array
    {
        $results = parent::toArray();

        $results['resources'] = array_map(fn(ResourceDefinition $resource) => $resource->toArray(), $this->resources);

        return $results;
    }
}
