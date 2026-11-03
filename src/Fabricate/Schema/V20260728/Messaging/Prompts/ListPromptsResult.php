<?php

namespace Fabricate\Schema\V20260728\Messaging\Prompts;

use Fabricate\Contracts\Schema\Cursor;
use Fabricate\Contracts\Schema\Messaging\Prompts\ListPromptsResult as ListPromptsContract;
use Fabricate\Contracts\Schema\Messaging\ServerResult;
use Fabricate\Schema\V20260728\Capabilities\Prompts\PromptDefinition;
use Fabricate\Schema\V20260728\Concerns\CacheScope;
use Fabricate\Schema\V20260728\Concerns\ResultTypeEnum;
use Fabricate\Schema\V20260728\Messaging\MetaObjects\ResultMetaObject;
use Fabricate\Schema\V20260728\Messaging\Pagination\PaginatedResult;

final readonly class ListPromptsResult extends PaginatedResult implements ListPromptsContract, ServerResult
{
    /**
     * @param PromptDefinition[] $prompts
     */
    public function __construct(
        public array $prompts,
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

        $results['prompts'] = array_map(fn(PromptDefinition $prompt) => $prompt->toArray(), $this->prompts);

        return $results;
    }
}
