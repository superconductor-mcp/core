<?php

namespace Fabricate\Schema\V20250618\Concerns;

use Fabricate\Contracts\Schema\Contracts\AudioContent as AudioContract;

readonly class AudioContent implements AudioContract, ContentBlock
{
    public string $type;

    public function __construct(
        public string $data,
        public string $mimeType,
        public ?Annotations $annotations = null,
        public ?array $_meta = null,
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
        if(isset($this->_meta)) $results['_meta'] = $this->_meta ?: (object) [];

        return $results;
    }
}
