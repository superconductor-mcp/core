<?php

namespace Fabricate\Schema\V20260728\Messaging\Discover;

use Fabricate\Contracts\Schema\Messaging\Discover\DiscoverResult as DiscoverContract;
use Fabricate\Contracts\Schema\Messaging\ServerResult;
use Fabricate\Schema\V20260728\Capabilities\ServerCapabilities;
use Fabricate\Schema\V20260728\Concerns\CacheScope;
use Fabricate\Schema\V20260728\Concerns\ResultTypeEnum;
use Fabricate\Schema\V20260728\Messaging\CacheableResult;
use Fabricate\Schema\V20260728\Messaging\MetaObjects\ResultMetaObject;

final readonly class DiscoverResult extends CacheableResult implements DiscoverContract, ServerResult
{
    /**
     * @param string[] $supportedVersions
     * @param ServerCapabilities $capabilities
     * @param int $ttlMs
     * @param CacheScope $cacheScope
     * @param string|ResultTypeEnum $resultType
     * @param string|null $instructions
     * @param ResultMetaObject|null $_meta
     */
    public function __construct(
        public array $supportedVersions,
        public ServerCapabilities $capabilities,
        int $ttlMs,
        CacheScope $cacheScope,
        string|ResultTypeEnum $resultType = ResultTypeEnum::COMPLETE,
        public ?string $instructions = null,
        ?ResultMetaObject $_meta = null,
    ) {
        parent::__construct($ttlMs, $cacheScope, $resultType, $_meta);
    }

    public function toArray(): array
    {
        $results = parent::toArray();

        $results['supportedVersions'] = $this->supportedVersions;
        $results['capabilities'] = $this->capabilities->toArray() ?: (object) [];
        if(isset($this->instructions)) $results['instructions'] = $this->instructions;

        return $results;
    }
}
