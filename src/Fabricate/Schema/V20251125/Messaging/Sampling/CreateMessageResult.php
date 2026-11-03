<?php

namespace Fabricate\Schema\V20251125\Messaging\Sampling;

use Fabricate\Contracts\Schema\Concerns\Role;
use Fabricate\Contracts\Schema\Messaging\ClientResult;
use Fabricate\Contracts\Schema\Messaging\Sampling\CreateMessageResult as CreateMessageContract;
use Fabricate\Schema\Common\Concerns\HasContentRole;
use Fabricate\Schema\V20251125\Concerns\SamplingMessageContentBlock;
use Fabricate\Schema\V20251125\Messaging\Result;

final readonly class CreateMessageResult extends Result implements CreateMessageContract, ClientResult
{
    use HasContentRole;

    /**
     * @param string $model
     * @param Role $role
     * @param SamplingMessageContentBlock|SamplingMessageContentBlock[] $content
     * @param string|null $stopReason
     * @param array|null $_meta
     */
    public function __construct(
        public string $model,
        public Role $role,
        public SamplingMessageContentBlock|array $content,
        public ?string $stopReason = null,
        ?array $_meta = null,
    ) {
        parent::__construct($_meta);
    }

    public function toArray(): array
    {
        $results = parent::toArray();

        $results['model'] = $this->model;
        $results['role'] = $this->role->value;
        $results['content'] = is_array($this->content)
            ? array_map(fn(SamplingMessageContentBlock $content) => $content->toArray(), $this->content)
            : $this->content->toArray();
        if(isset($this->stopReason)) $results['stopReason'] = $this->stopReason;

        return $results;
    }
}
