<?php

namespace Fabricate\Schema\V20260728\Capabilities\Resources;

use Fabricate\Schema\V20260728\MetaObject;

readonly class BlobResourceContents extends ResourceContents
{
    public function __construct(
        public string $blob,
        string $uri,
        ?string $mimeType = null,
        ?MetaObject $_meta = null,
    ) {
        parent::__construct($uri, $mimeType, $_meta);
    }

    public function toArray(): array
    {
        $results = parent::toArray();

        $results['blob'] = $this->blob;

        return $results;
    }
}
