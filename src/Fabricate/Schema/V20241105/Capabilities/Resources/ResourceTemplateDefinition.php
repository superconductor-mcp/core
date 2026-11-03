<?php

namespace Fabricate\Schema\V20241105\Capabilities\Resources;

use Fabricate\Contracts\Schema\Capabilities\ResourceTemplateDefinition as TemplateContract;
use Fabricate\Contracts\Schema\Concerns\Role;
use Fabricate\Schema\V20241105\Concerns\Annotated;

readonly class ResourceTemplateDefinition extends Annotated implements TemplateContract
{
    /**
     * @param Role[]|null $audience
     */
    public function __construct(
        public string $uriTemplate,
        public string $name,
        public ?string $description = null,
        public ?string $mimeType = null,
        ?array $audience = null,
        int|float|null $priority = null,
    ) {
        parent::__construct($audience, $priority);
    }

    public function toArray(): array
    {
        $results = parent::toArray();

        $results['uriTemplate'] = $this->uriTemplate;
        $results['name'] = $this->name;
        if(isset($this->description)) $results['description'] = $this->description;
        if(isset($this->mimeType)) $results['mimeType'] = $this->mimeType;

        return $results;
    }
}
