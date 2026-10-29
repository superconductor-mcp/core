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
        public ?string $title = null,
        public array $meta = [],
        /** @var list<Icon> 2025-11-25: images a client may show; an array entry is read as {src, mimeType?, sizes?, theme?} */
        public array $icons = [],
    ) {
        Meta::check($meta);
        $this->icons = Icon::list($icons);
    }

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
        return Meta::attach(array_filter([
            'name' => $this->name,
            'title' => $this->title,
            'description' => $this->description,
            'icons' => Icon::render($this->icons),
            // a prompt with no arguments leaves the key out
            'arguments' => $this->arguments === []
                ? null
                : array_map(fn (PromptArgument $argument): array => $argument->toArray(), array_values($this->arguments)),
        ], fn (mixed $value): bool => ! is_null($value)), $this->meta);
    }
}
