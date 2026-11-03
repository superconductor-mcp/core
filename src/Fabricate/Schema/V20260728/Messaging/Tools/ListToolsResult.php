<?php

namespace Fabricate\Schema\V20260728\Messaging\Tools;

use Fabricate\Contracts\Schema\Cursor;
use Fabricate\Contracts\Schema\Messaging\ServerResult;
use Fabricate\Contracts\Schema\Messaging\Tools\ListToolsResult as ListToolsContract;
use Fabricate\Schema\V20260728\Capabilities\Tools\ToolDefinition;
use Fabricate\Schema\V20260728\Concerns\CacheScope;
use Fabricate\Schema\V20260728\Concerns\ResultTypeEnum;
use Fabricate\Schema\V20260728\Messaging\MetaObjects\ResultMetaObject;
use Fabricate\Schema\V20260728\Messaging\Pagination\PaginatedResult;

final readonly class ListToolsResult extends PaginatedResult implements ListToolsContract, ServerResult
{
    /**
     * @param ToolDefinition[] $tools
     */
    public function __construct(
        public array $tools,
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

        $results['tools'] = array_map(fn(ToolDefinition $tool) => $tool->toArray(), $this->tools);

        return $results;
    }
}
