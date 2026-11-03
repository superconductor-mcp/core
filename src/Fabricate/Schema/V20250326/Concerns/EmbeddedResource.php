<?php

namespace Fabricate\Schema\V20250326\Concerns;

use Fabricate\Contracts\Schema\Capabilities\EmbeddedResource as EmbeddedContract;
use Fabricate\Schema\V20250326\Capabilities\Resources\BlobResourceContents;
use Fabricate\Schema\V20250326\Capabilities\Resources\TextResourceContents;

readonly class EmbeddedResource implements EmbeddedContract
{
    public string $type;

    public function __construct(
        public TextResourceContents|BlobResourceContents $resource,
        public ?Annotations $annotations = null,
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

        return $results;
    }
}
