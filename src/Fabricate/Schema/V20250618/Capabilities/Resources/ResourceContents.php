<?php

namespace Fabricate\Schema\V20250618\Capabilities\Resources;

use Voyager\Contracts\NutsAndBolts\Arrayable;

abstract readonly class ResourceContents implements Arrayable
{
    public function __construct(
        public string  $uri,
        public ?string $mimeType = null,
        public ?array $_meta = null,
    ) {}

    public function toArray(): array
    {
        $results = ['uri' => $this->uri];

        if(isset($this->mimeType)) $results['mimeType'] = $this->mimeType;
        if(isset($this->_meta)) $results['_meta'] = $this->_meta ?: (object) [];

        return $results;
    }
}
