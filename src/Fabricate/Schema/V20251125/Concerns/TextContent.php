<?php

namespace Fabricate\Schema\V20251125\Concerns;

use Fabricate\Contracts\Schema\Contracts\TextContent as TextContract;

readonly class TextContent implements TextContract, ContentBlock, SamplingMessageContentBlock
{
    public string $type;

    public function __construct(
        public string $text,
        public ?Annotations $annotations = null,
        public ?array $_meta = null,
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
        if(isset($this->_meta)) $results['_meta'] = $this->_meta ?: (object) [];

        return $results;
    }
}
