<?php

namespace Fabricate\Schema\V20250618\Capabilities\Resources;

use Fabricate\Contracts\Schema\Capabilities\ResourceTemplateDefinition as TemplateContract;
use Fabricate\Schema\Common\Concerns\BaseMetaData;
use Fabricate\Schema\V20250618\Concerns\Annotations;

readonly class ResourceTemplateDefinition extends BaseMetaData implements TemplateContract
{
    public function __construct(
        public string $uriTemplate,
        string $name,
        public ?string $description = null,
        public ?string $mimeType = null,
        public ?Annotations $annotations = null,
        ?string $title = null,
        public ?array $_meta = null,
    ) {
        parent::__construct($name, $title);
    }

    public function toArray(): array
    {
        $results = parent::toArray();

        $results['uriTemplate'] = $this->uriTemplate;
        if(isset($this->description)) $results['description'] = $this->description;
        if(isset($this->mimeType)) $results['mimeType'] = $this->mimeType;
        if(isset($this->annotations)) $results['annotations'] = $this->annotations->toArray() ?: (object) [];
        if(isset($this->_meta)) $results['_meta'] = $this->_meta ?: (object) [];

        return $results;
    }
}
