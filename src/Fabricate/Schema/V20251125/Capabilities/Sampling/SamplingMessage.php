<?php

namespace Fabricate\Schema\V20251125\Capabilities\Sampling;

use Fabricate\Contracts\Schema\Capabilities\SamplingMessage as SamplingContract;
use Fabricate\Contracts\Schema\Concerns\Role;
use Fabricate\Schema\Common\Concerns\HasContentRole;
use Fabricate\Schema\V20251125\Concerns\SamplingMessageContentBlock;

readonly class SamplingMessage implements SamplingContract
{
    use HasContentRole;

    /**
     * @param Role $role
     * @param SamplingMessageContentBlock|SamplingMessageContentBlock[] $content
     * @param array|null $_meta
     */
    public function __construct(
        public Role $role,
        public SamplingMessageContentBlock|array $content,
        public ?array $_meta = null,
    ) {}

    public function toArray(): array
    {
        $results = [
            'role' => $this->role->value,
            'content' => is_array($this->content)
                ? array_map(fn(SamplingMessageContentBlock $content) => $content->toArray(), $this->content)
                : $this->content->toArray(),
        ];

        if(isset($this->_meta)) $results['_meta'] = $this->_meta ?: (object) [];

        return $results;
    }
}
