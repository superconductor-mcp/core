<?php

namespace Fabricate\Schema\V20250326\Capabilities\Resources;

use Fabricate\Contracts\Schema\Capabilities\ResourceTemplateDefinition as TemplateContract;
use Fabricate\Schema\V20250326\Concerns\Annotations;

readonly class ResourceTemplateDefinition implements TemplateContract
{
    public function __construct(
        public string $uriTemplate,
        public string $name,
        public ?string $description = null,
        public ?string $mimeType = null,
        public ?Annotations $annotations = null,
    ) {}

    public function toArray(): array
    {
        $results = [
            'uriTemplate' => $this->uriTemplate,
            'name' => $this->name,
        ];

        if(isset($this->description)) $results['description'] = $this->description;
        if(isset($this->mimeType)) $results['mimeType'] = $this->mimeType;
        if(isset($this->annotations)) $results['annotations'] = $this->annotations->toArray() ?: (object) [];

        return $results;
    }
}
