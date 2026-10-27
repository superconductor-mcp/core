<?php

namespace Fabricate\NutsAndBolts\Schema;

use Fabricate\NutsAndBolts\Meta;
use InvalidArgumentException;
use Voyager\Contracts\NutsAndBolts\Arrayable;

// the contents of one resource: what resources/read returns, and what an embedded resource carries
class ResourceContents implements Arrayable
{
    /**
     * @param string|null $text the contents of a text resource
     * @param string|null $blob the contents of a binary resource, base64-encoded
     */
    public function __construct(
        public string $uri,
        public ?string $mime_type = null,
        public ?string $text = null,
        public ?string $blob = null,
        public array $meta = [],
    ) {
        Meta::check($meta);

        if (is_null($text) === is_null($blob)) {
            throw new InvalidArgumentException('ResourceContents needs exactly one of text or blob.');
        }

        if (! is_null($blob) && base64_decode($blob, true) === false) {
            throw new InvalidArgumentException('ResourceContents blob must be base64-encoded.');
        }
    }

    public function toArray(): array
    {
        return Meta::attach(array_filter([
            'uri' => $this->uri,
            'mimeType' => $this->mime_type,
            'text' => $this->text,
            'blob' => $this->blob,
        ], fn (?string $value): bool => ! is_null($value)), $this->meta);
    }
}
