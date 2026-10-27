<?php

namespace Fabricate\NutsAndBolts;

use Closure;
use Voyager\Contracts\NutsAndBolts\Arrayable;

// one argument a prompt takes; prompt arguments are always strings
class PromptArgument implements Arrayable
{
    public function __construct(
        public string $name,
        public ?string $description = null,
        public bool $required = false,
        /** @var Closure|class-string|null suggests values for completion/complete */
        public Closure|string|null $completer = null,
        public ?string $title = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'title' => $this->title,
            'description' => $this->description,
            // required defaults to false, so it only goes out when true
            'required' => $this->required ?: null,
        ], fn (mixed $value): bool => ! is_null($value));
    }
}
