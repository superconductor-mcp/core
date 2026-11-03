<?php

namespace Fabricate\Schema\V20241105\Capabilities\Prompts;

use Fabricate\Contracts\Schema\Concerns\Role;
use Fabricate\Schema\V20241105\Concerns\EmbeddedResource;
use Fabricate\Schema\V20241105\Concerns\ImageContent;
use Fabricate\Schema\V20241105\Concerns\TextContent;
use Voyager\Contracts\NutsAndBolts\Arrayable;

readonly class PromptMessage implements Arrayable
{
    public function __construct(
        public Role $role,
        public TextContent|ImageContent|EmbeddedResource $content,
    ) {}

    public function toArray(): array
    {
        return [
            'role' => $this->role->value,
            'content' => $this->content->toArray(),
        ];
    }
}
