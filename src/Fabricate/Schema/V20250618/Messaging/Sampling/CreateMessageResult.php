<?php

namespace Fabricate\Schema\V20250618\Messaging\Sampling;

use Fabricate\Contracts\Schema\Concerns\Role;
use Fabricate\Contracts\Schema\Messaging\ClientResult;
use Fabricate\Contracts\Schema\Messaging\Sampling\CreateMessageResult as CreateMessageContract;
use Fabricate\Schema\Common\Concerns\HasContentRole;
use Fabricate\Schema\V20250618\Concerns\AudioContent;
use Fabricate\Schema\V20250618\Concerns\ImageContent;
use Fabricate\Schema\V20250618\Concerns\TextContent;
use Fabricate\Schema\V20250618\Messaging\Result;

final readonly class CreateMessageResult extends Result implements CreateMessageContract, ClientResult
{
    use HasContentRole;

    public function __construct(
        public string $model,
        public Role $role,
        public TextContent|ImageContent|AudioContent $content,
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
        $results['content'] = $this->content->toArray();
        if(isset($this->stopReason)) $results['stopReason'] = $this->stopReason;

        return $results;
    }
}
