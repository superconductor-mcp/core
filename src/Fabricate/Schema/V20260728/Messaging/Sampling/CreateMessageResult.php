<?php

namespace Fabricate\Schema\V20260728\Messaging\Sampling;

use Fabricate\Contracts\Schema\Concerns\Role;
use Fabricate\Contracts\Schema\Messaging\Sampling\CreateMessageResult as CreateMessageContract;
use Fabricate\Schema\V20260728\Capabilities\Sampling\SamplingMessage;
use Fabricate\Schema\V20260728\Concerns\InputResponse;
use Fabricate\Schema\V20260728\Concerns\SamplingMessageContentBlock;
use Fabricate\Schema\V20260728\MetaObject;

final readonly class CreateMessageResult extends SamplingMessage implements CreateMessageContract, InputResponse
{
    /**
     * @param string $model
     * @param Role $role
     * @param SamplingMessageContentBlock|SamplingMessageContentBlock[] $content
     * @param string|null $stopReason
     * @param MetaObject|null $_meta
     */
    public function __construct(
        public string $model,
        Role $role,
        SamplingMessageContentBlock|array $content,
        public ?string $stopReason = null,
        ?MetaObject $_meta = null,
    ) {
        parent::__construct($role, $content, $_meta);
    }

    public function toArray(): array
    {
        $results = parent::toArray();

        $results['model'] = $this->model;
        if(isset($this->stopReason)) $results['stopReason'] = $this->stopReason;

        return $results;
    }
}
