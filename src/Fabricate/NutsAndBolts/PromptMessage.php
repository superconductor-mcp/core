<?php

namespace Fabricate\NutsAndBolts;

use Voyager\Contracts\NutsAndBolts\Arrayable;
use Fabricate\Contracts\Mechanism\SchemaPrimitive;

// one message of a prompt: a role and one piece of content (text, image or embedded resource)
class PromptMessage implements Arrayable
{
    public function __construct(
        public Role $role,
        public SchemaPrimitive&Arrayable $content,
    ) {}

    public static function user(SchemaPrimitive&Arrayable $content): self
    {
        return new self(Role::User, $content);
    }

    public static function assistant(SchemaPrimitive&Arrayable $content): self
    {
        return new self(Role::Assistant, $content);
    }

    public function toArray(): array
    {
        return [
            'role' => $this->role->value,
            'content' => $this->content->toArray(),
        ];
    }
}
