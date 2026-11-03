<?php

namespace Fabricate\Schema\V20260728\Concerns;

use Fabricate\Contracts\Schema\Contracts\ImageContent as ImageContract;
use Fabricate\Schema\V20260728\MetaObject;

readonly class ImageContent implements ImageContract, ContentBlock, SamplingMessageContentBlock
{
    public string $type;

    public function __construct(
        public string $data,
        public string $mimeType,
        public ?Annotations $annotations = null,
        public ?MetaObject $_meta = null,
    ) {
        $this->type = 'image';
    }

    public function toArray(): array
    {
        $results = [
            'type' => $this->type,
            'data' => $this->data,
            'mimeType' => $this->mimeType,
        ];

        if(isset($this->annotations)) $results['annotations'] = $this->annotations->toArray() ?: (object) [];
        if(isset($this->_meta)) $results['_meta'] = $this->_meta->toArray() ?: (object) [];

        return $results;
    }
}
