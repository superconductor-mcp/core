<?php

namespace Fabricate\NutsAndBolts\Schema;

use InvalidArgumentException;
use Voyager\Contracts\NutsAndBolts\Arrayable;
use Fabricate\Contracts\Mechanism\SchemaPrimitive;

class ImageProperty implements Arrayable, SchemaPrimitive
{
    /**
     * @param string $data the image bytes, base64-encoded
     * @param string $mime_type e.g. image/png
     */
    public function __construct(
        public string $data,
        public string $mime_type,
    ) {
        if (base64_decode($data, true) === false) {
            throw new InvalidArgumentException('ImageProperty data must be base64-encoded.');
        }

        if (! str_starts_with($mime_type, 'image/')) {
            throw new InvalidArgumentException("ImageProperty mime type must be an image type, got [{$mime_type}].");
        }
    }

    // one image item of a tool result's content
    public function toArray(): array
    {
        return [
            'type' => 'image',
            'data' => $this->data,
            'mimeType' => $this->mime_type,
        ];
    }
}
