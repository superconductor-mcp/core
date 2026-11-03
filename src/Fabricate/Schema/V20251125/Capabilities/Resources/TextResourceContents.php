<?php

namespace Fabricate\Schema\V20251125\Capabilities\Resources;

readonly class TextResourceContents extends ResourceContents
{
    public function __construct(
        public string $text,
        string $uri,
        ?string $mimeType = null,
        ?array $_meta = null,
    ) {
        parent::__construct($uri, $mimeType, $_meta);
    }

    public function toArray(): array
    {
        $results = parent::toArray();

        $results['text'] = $this->text;

        return $results;
    }
}
