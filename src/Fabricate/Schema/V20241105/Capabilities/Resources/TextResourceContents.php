<?php

namespace Fabricate\Schema\V20241105\Capabilities\Resources;

readonly class TextResourceContents extends ResourceContents
{
    public function __construct(
        public string $text,
        string $uri,
        ?string $mimeType = null,
    ) {
        parent::__construct($uri, $mimeType);
    }

    public function toArray(): array
    {
        $results = parent::toArray();

        $results['text'] = $this->text;

        return $results;
    }
}
