<?php

namespace Fabricate\Schema\V20241105\Capabilities\Resources;

readonly class BlobResourceContents extends ResourceContents
{
    public function __construct(
        public string $blob,
        string $uri,
        ?string $mimeType = null,
    ) {
        parent::__construct($uri, $mimeType);
    }

    public function toArray(): array
    {
        $results = parent::toArray();

        $results['blob'] = $this->blob;

        return $results;
    }
}
