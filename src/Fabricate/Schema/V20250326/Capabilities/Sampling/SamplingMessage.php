<?php

namespace Fabricate\Schema\V20250326\Capabilities\Sampling;

use Fabricate\Contracts\Schema\Capabilities\SamplingMessage as SamplingContract;
use Fabricate\Contracts\Schema\Concerns\Role;
use Fabricate\Schema\Common\Concerns\HasContentRole;
use Fabricate\Schema\V20250326\Concerns\AudioContent;
use Fabricate\Schema\V20250326\Concerns\ImageContent;
use Fabricate\Schema\V20250326\Concerns\TextContent;

readonly class SamplingMessage implements SamplingContract
{
    use HasContentRole;

    public function __construct(
        public Role $role,
        public TextContent|ImageContent|AudioContent $content,
    ) {}

    public function toArray(): array
    {
        return [
            'role' => $this->role->value,
            'content' => $this->content->toArray(),
        ];
    }
}
