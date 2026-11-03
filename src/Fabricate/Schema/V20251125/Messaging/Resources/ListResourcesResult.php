<?php

namespace Fabricate\Schema\V20251125\Messaging\Resources;

use Fabricate\Contracts\Schema\Cursor;
use Fabricate\Contracts\Schema\Messaging\Resources\ListResourcesResult as ListResourcesContract;
use Fabricate\Contracts\Schema\Messaging\ServerResult;
use Fabricate\Schema\V20251125\Capabilities\Resources\ResourceDefinition;
use Fabricate\Schema\V20251125\Messaging\Pagination\PaginatedResult;

final readonly class ListResourcesResult extends PaginatedResult implements ListResourcesContract, ServerResult
{
    /**
     * @param ResourceDefinition[] $resources
     */
    public function __construct(
        public array $resources,
        ?Cursor $nextCursor = null,
        ?array $_meta = null
    ) {
        parent::__construct($nextCursor, $_meta);
    }

    public function toArray(): array
    {
        $results = parent::toArray();

        $results['resources'] = array_map(fn(ResourceDefinition $resource) => $resource->toArray(), $this->resources);

        return $results;
    }
}
