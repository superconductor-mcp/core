<?php

namespace Fabricate\NutsAndBolts;

use Fabricate\NutsAndBolts\Schema\Annotations;
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
        public ?string $title = null,
        public ?Annotations $annotations = null,
        public array $meta = [],
    ) {
        Meta::check($meta);
    }

    public function toArray(): array
    {
        return Meta::attach(array_filter([
            'uri' => $this->uri,
            'name' => $this->name,
            'title' => $this->title,
            'description' => $this->description,
            'mimeType' => $this->mime_type,
            'annotations' => $this->annotations?->toArray(),
        ], fn (mixed $value): bool => ! is_null($value)), $this->meta);
    }
}
