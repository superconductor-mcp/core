<?php

namespace Fabricate\NutsAndBolts;

use Closure;
use Voyager\Contracts\NutsAndBolts\Arrayable;

// one fixed resource a server offers in resources/list, plus the action resources/read runs for it
class ResourceDefinition implements Arrayable
{
    public function __construct(
        public string $uri,
        public string $name,
        public ?string $description = null,
        public ?string $mime_type = null,
        public Closure|string|null $action = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'uri' => $this->uri,
            'name' => $this->name,
            'description' => $this->description,
            'mimeType' => $this->mime_type,
        ], fn (?string $value): bool => ! is_null($value));
    }
}
