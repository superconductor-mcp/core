<?php

namespace Fabricate\NutsAndBolts;

use Closure;
use Voyager\Contracts\NutsAndBolts\Arrayable;

// one prompt a server offers in prompts/list, plus the action prompts/get runs for it
class PromptDefinition implements Arrayable
{
    /**
     * @param list<PromptArgument> $arguments
     */
    public function __construct(
        public string $name,
        public ?string $description = null,
        public array $arguments = [],
        public Closure|string|null $action = null,
    ) {}

    public function argument(string $name): ?PromptArgument
    {
        foreach ($this->arguments as $argument) {
            if ($argument->name === $name) {
                return $argument;
            }
        }

        return null;
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'description' => $this->description,
            // a prompt with no arguments leaves the key out
            'arguments' => $this->arguments === []
                ? null
                : array_map(fn (PromptArgument $argument): array => $argument->toArray(), array_values($this->arguments)),
        ], fn (mixed $value): bool => ! is_null($value));
    }
}
