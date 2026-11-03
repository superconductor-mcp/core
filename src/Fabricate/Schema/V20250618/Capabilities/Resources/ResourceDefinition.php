<?php

namespace Fabricate\Schema\V20250618\Capabilities\Resources;

use Fabricate\Contracts\Schema\Capabilities\ResourceDefinition as ResourceDefinitionContract;
use Fabricate\Schema\Common\Concerns\BaseMetaData;
use Fabricate\Schema\V20250618\Concerns\Annotations;

readonly class ResourceDefinition extends BaseMetaData implements ResourceDefinitionContract
{
    public function __construct(
        public string $uri,
        string $name,
        public ?string $description = null,
        public ?string $mimeType = null,
        public ?Annotations $annotations = null,
        public ?int $size = null,
        ?string $title = null,
        public ?array $_meta = null,
    ) {
        parent::__construct($name, $title);
    }

    public function toArray(): array
    {
        $results = parent::toArray();

        $results['uri'] = $this->uri;
        if(isset($this->description)) $results['description'] = $this->description;
        if(isset($this->mimeType)) $results['mimeType'] = $this->mimeType;
        if(isset($this->annotations)) $results['annotations'] = $this->annotations->toArray() ?: (object) [];
        if(isset($this->size)) $results['size'] = $this->size;
        if(isset($this->_meta)) $results['_meta'] = $this->_meta ?: (object) [];

        return $results;
    }
}
