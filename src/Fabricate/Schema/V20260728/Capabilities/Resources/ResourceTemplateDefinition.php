<?php

namespace Fabricate\Schema\V20260728\Capabilities\Resources;

use Fabricate\Contracts\Schema\Capabilities\ResourceTemplateDefinition as TemplateContract;
use Fabricate\Schema\Common\Concerns\BaseMetaData;
use Fabricate\Schema\Common\Concerns\Icon;
use Fabricate\Schema\V20260728\Concerns\Annotations;
use Fabricate\Schema\V20260728\MetaObject;

readonly class ResourceTemplateDefinition extends BaseMetaData implements TemplateContract
{
    /**
     * @param Icon[]|null $icons
     */
    public function __construct(
        public string $uriTemplate,
        string $name,
        public ?string $description = null,
        public ?string $mimeType = null,
        public ?Annotations $annotations = null,
        public ?array $icons = null,
        ?string $title = null,
        public ?MetaObject $_meta = null,
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
        if(isset($this->icons)) $results['icons'] = array_map(fn(Icon $icon) => $icon->toArray(), $this->icons);
        if(isset($this->_meta)) $results['_meta'] = $this->_meta->toArray() ?: (object) [];

        return $results;
    }
}
