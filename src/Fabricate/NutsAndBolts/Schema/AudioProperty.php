<?php

namespace Fabricate\NutsAndBolts\Schema;

use Fabricate\NutsAndBolts\Meta;
use InvalidArgumentException;
use Voyager\Contracts\NutsAndBolts\Arrayable;
use Fabricate\Contracts\Mechanism\SchemaPrimitive;

class AudioProperty implements Arrayable, SchemaPrimitive
{
    /**
     * @param string $data the audio bytes, base64-encoded
     * @param string $mime_type e.g. audio/wav
     */
    public function __construct(
        public string $data,
        public string $mime_type,
        public ?Annotations $annotations = null,
        public array $meta = [],
    ) {
        Meta::check($meta);

        if (base64_decode($data, true) === false) {
            throw new InvalidArgumentException('AudioProperty data must be base64-encoded.');
        }

        if (! str_starts_with($mime_type, 'audio/')) {
            throw new InvalidArgumentException("AudioProperty mime type must be an audio type, got [{$mime_type}].");
        }
    }

    // 2025-03-26: one audio item of a tool result's content
    public function toArray(): array
    {
        return Meta::attach(array_filter([
            'type' => 'audio',
            'data' => $this->data,
            'mimeType' => $this->mime_type,
            'annotations' => $this->annotations?->toArray(),
        ], fn (mixed $value): bool => ! is_null($value)), $this->meta);
    }
}
