<?php

namespace Fabricate\NutsAndBolts\Schema;

use Voyager\Contracts\NutsAndBolts\Arrayable;
use Fabricate\Contracts\Mechanism\SchemaPrimitive;

class EmbeddedResourceProperty implements Arrayable, SchemaPrimitive
{
    public ResourceContents $contents;

    /**
     * A resource carried inside a tool result: its uri plus either its text or its bytes, never both.
     *
     * @param string|null $text the contents of a text resource
     * @param string|null $blob the contents of a binary resource, base64-encoded
     */
    public function __construct(
        public string $uri,
        public ?string $mime_type = null,
        public ?string $text = null,
        public ?string $blob = null,
    ) {
        // the same contents shape resources/read returns, checked in one place
        $this->contents = new ResourceContents($uri, $mime_type, $text, $blob);
    }

    // one embedded-resource item of a tool result's content
    public function toArray(): array
    {
        return [
            'type' => 'resource',
            'resource' => $this->contents->toArray(),
        ];
    }
}
