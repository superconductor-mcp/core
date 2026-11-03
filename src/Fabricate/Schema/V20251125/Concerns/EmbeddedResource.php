<?php

namespace Fabricate\Schema\V20251125\Concerns;

use Fabricate\Contracts\Schema\Capabilities\EmbeddedResource as EmbeddedContract;
use Fabricate\Schema\V20251125\Capabilities\Resources\BlobResourceContents;
use Fabricate\Schema\V20251125\Capabilities\Resources\TextResourceContents;

readonly class EmbeddedResource implements EmbeddedContract, ContentBlock
{
    public string $type;

    public function __construct(
        public TextResourceContents|BlobResourceContents $resource,
        public ?Annotations $annotations = null,
        public ?array $_meta = null,
    ) {
        $this->type = 'resource';
    }

    public function toArray(): array
    {
        $results = [
            'type' => $this->type,
            'resource' => $this->resource->toArray(),
        ];

        if(isset($this->annotations)) $results['annotations'] = $this->annotations->toArray() ?: (object) [];
        if(isset($this->_meta)) $results['_meta'] = $this->_meta ?: (object) [];

        return $results;
    }
}
