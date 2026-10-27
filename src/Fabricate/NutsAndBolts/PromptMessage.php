<?php

namespace Fabricate\NutsAndBolts;

use InvalidArgumentException;
use Fabricate\NutsAndBolts\Schema\Content;

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

    /**
     * A message the server sent, read into the same shape this side sends.
     *
     * @throws InvalidArgumentException naming what is wrong
     */
    public static function fromArray(mixed $message): self
    {
        $role = is_array($message) && is_string($message['role'] ?? null) ? Role::tryFrom($message['role']) : null;

        if (is_null($role)) {
            throw new InvalidArgumentException('a prompt message needs a role of user or assistant.');
        }

        return new self($role, Content::item($message['content'] ?? null));
    }

    public function toArray(): array
    {
        return [
            'role' => $this->role->value,
            'content' => $this->content->toArray(),
        ];
    }
}
