<?php

namespace Fabricate\Schema\V20250326\Capabilities\Resources;

use Fabricate\Contracts\Schema\Capabilities\ResourceDefinition as ResourceDefinitionContract;
use Fabricate\Schema\V20250326\Concerns\Annotations;

readonly class ResourceDefinition implements ResourceDefinitionContract
{
    public function __construct(
        public string $uri,
        public string $name,
        public ?string $description = null,
        public ?string $mimeType = null,
        public ?Annotations $annotations = null,
        public ?int $size = null,
    ) {}

    public function toArray(): array
    {
        $results = [
            'uri' => $this->uri,
            'name' => $this->name,
        ];

        if(isset($this->description)) $results['description'] = $this->description;
        if(isset($this->mimeType)) $results['mimeType'] = $this->mimeType;
        if(isset($this->annotations)) $results['annotations'] = $this->annotations->toArray() ?: (object) [];
        if(isset($this->size)) $results['size'] = $this->size;

        return $results;
    }
}
