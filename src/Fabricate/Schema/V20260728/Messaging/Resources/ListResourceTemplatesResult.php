<?php

namespace Fabricate\Schema\V20260728\Messaging\Resources;

use Fabricate\Contracts\Schema\Cursor;
use Fabricate\Contracts\Schema\Messaging\Resources\ListResourceTemplatesResult as ListTemplatesContract;
use Fabricate\Contracts\Schema\Messaging\ServerResult;
use Fabricate\Schema\V20260728\Capabilities\Resources\ResourceTemplateDefinition;
use Fabricate\Schema\V20260728\Concerns\CacheScope;
use Fabricate\Schema\V20260728\Concerns\ResultTypeEnum;
use Fabricate\Schema\V20260728\Messaging\MetaObjects\ResultMetaObject;
use Fabricate\Schema\V20260728\Messaging\Pagination\PaginatedResult;

final readonly class ListResourceTemplatesResult extends PaginatedResult implements ListTemplatesContract, ServerResult
{
    /**
     * @param ResourceTemplateDefinition[] $resourceTemplates
     */
    public function __construct(
        public array $resourceTemplates,
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

        $results['resourceTemplates'] = array_map(fn(ResourceTemplateDefinition $template) => $template->toArray(), $this->resourceTemplates);

        return $results;
    }
}
