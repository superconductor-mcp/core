<?php

namespace Fabricate\Schema\V20260728\Messaging\Resources;

use Fabricate\Contracts\Schema\Messaging\Resources\ReadResourceResult as ReadResourceContract;
use Fabricate\Contracts\Schema\Messaging\ServerResult;
use Fabricate\Schema\V20260728\Capabilities\Resources\BlobResourceContents;
use Fabricate\Schema\V20260728\Capabilities\Resources\TextResourceContents;
use Fabricate\Schema\V20260728\Concerns\CacheScope;
use Fabricate\Schema\V20260728\Concerns\ResultTypeEnum;
use Fabricate\Schema\V20260728\Messaging\CacheableResult;
use Fabricate\Schema\V20260728\Messaging\MetaObjects\ResultMetaObject;

final readonly class ReadResourceResult extends CacheableResult implements ReadResourceContract, ServerResult
{
    /**
     * @param (TextResourceContents|BlobResourceContents)[] $contents
     */
    public function __construct(
        public array $contents,
        int $ttlMs,
        CacheScope $cacheScope,
        string|ResultTypeEnum $resultType = ResultTypeEnum::COMPLETE,
        ?ResultMetaObject $_meta = null,
    ) {
        parent::__construct($ttlMs, $cacheScope, $resultType, $_meta);
    }

    public function toArray(): array
    {
        $results = parent::toArray();

        $results['contents'] = array_map(fn(TextResourceContents|BlobResourceContents $content) => $content->toArray(), $this->contents);

        return $results;
    }
}
