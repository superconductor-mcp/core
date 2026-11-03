<?php

namespace Fabricate\Schema\V20251125\Capabilities\Prompts;

use Fabricate\Contracts\Schema\Concerns\Role;
use Fabricate\Schema\V20251125\Concerns\ContentBlock;
use Voyager\Contracts\NutsAndBolts\Arrayable;

readonly class PromptMessage implements Arrayable
{
    public function __construct(
        public Role $role,
        public ContentBlock $content,
    ) {}

    public function toArray(): array
    {
        return [
            'role' => $this->role->value,
            'content' => $this->content->toArray(),
        ];
    }
}
