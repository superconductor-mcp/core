<?php

namespace Fabricate\Schema\V20260728\Capabilities\Resources;

use Fabricate\Schema\V20260728\MetaObject;
use Voyager\Contracts\NutsAndBolts\Arrayable;

abstract readonly class ResourceContents implements Arrayable
{
    public function __construct(
        public string  $uri,
        public ?string $mimeType = null,
        public ?MetaObject $_meta = null,
    ) {}

    public function toArray(): array
    {
        $results = ['uri' => $this->uri];

        if(isset($this->mimeType)) $results['mimeType'] = $this->mimeType;
        if(isset($this->_meta)) $results['_meta'] = $this->_meta->toArray() ?: (object) [];

        return $results;
    }
}
