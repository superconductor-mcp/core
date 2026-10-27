<?php

namespace Fabricate\NutsAndBolts\Schema;

use Fabricate\NutsAndBolts\Meta;
use Voyager\Contracts\NutsAndBolts\Arrayable;
use Fabricate\Contracts\Mechanism\SchemaPrimitive;

// 2025-06-18: a tool result item pointing at a resource the client can read or subscribe to; it need not be in resources/list
class ResourceLinkProperty implements Arrayable, SchemaPrimitive
{
    public function __construct(
        public string $uri,
        public string $name,
        public ?string $title = null,
        public ?string $description = null,
        public ?string $mime_type = null,
        public ?Annotations $annotations = null,
        public array $meta = [],
    ) {
        Meta::check($meta);
    }

    public function toArray(): array
    {
        return Meta::attach(array_filter([
            'type' => 'resource_link',
            'uri' => $this->uri,
            'name' => $this->name,
            'title' => $this->title,
            'description' => $this->description,
            'mimeType' => $this->mime_type,
            'annotations' => $this->annotations?->toArray(),
        ], fn (mixed $value): bool => ! is_null($value)), $this->meta);
    }
}
