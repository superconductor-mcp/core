<?php

namespace Fabricate\Schema\V20260728\Concerns;

use Fabricate\Contracts\Schema\Contracts\TextContent as TextContract;
use Fabricate\Schema\V20260728\MetaObject;

readonly class TextContent implements TextContract, ContentBlock, SamplingMessageContentBlock
{
    public string $type;

    public function __construct(
        public string $text,
        public ?Annotations $annotations = null,
        public ?MetaObject $_meta = null,
    ) {
        $this->type = 'text';
    }

    public function toArray(): array
    {
        $results = [
            'type' => $this->type,
            'text' => $this->text,
        ];

        if(isset($this->annotations)) $results['annotations'] = $this->annotations->toArray() ?: (object) [];
        if(isset($this->_meta)) $results['_meta'] = $this->_meta->toArray() ?: (object) [];

        return $results;
    }
}
