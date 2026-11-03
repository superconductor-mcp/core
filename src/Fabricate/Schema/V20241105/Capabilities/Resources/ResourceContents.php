<?php

namespace Fabricate\Schema\V20241105\Capabilities\Resources;

use Voyager\Contracts\NutsAndBolts\Arrayable;

abstract readonly class ResourceContents implements Arrayable
{
    public function __construct(
        public string  $uri,
        public ?string $mimeType = null,
    ) {}

    public function toArray(): array
    {
        $results = ['uri' => $this->uri];

        if(isset($this->mimeType)) $results['mimeType'] = $this->mimeType;

        return $results;
    }
}
