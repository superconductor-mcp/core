<?php

namespace Fabricate\Schema\V20251125\Capabilities\Resources;

readonly class BlobResourceContents extends ResourceContents
{
    public function __construct(
        public string $blob,
        string $uri,
        ?string $mimeType = null,
        ?array $_meta = null,
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
