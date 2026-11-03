<?php

namespace Fabricate\Schema\V20250326\Concerns;

use Fabricate\Contracts\Schema\Contracts\AudioContent as AudioContract;

readonly class AudioContent implements AudioContract
{
    public string $type;

    public function __construct(
        public string $data,
        public string $mimeType,
        public ?Annotations $annotations = null,
    ) {
        $this->type = 'audio';
    }

    public function toArray(): array
    {
        $results = [
            'type' => $this->type,
            'data' => $this->data,
            'mimeType' => $this->mimeType,
        ];

        if(isset($this->annotations)) $results['annotations'] = $this->annotations->toArray() ?: (object) [];

        return $results;
    }
}
