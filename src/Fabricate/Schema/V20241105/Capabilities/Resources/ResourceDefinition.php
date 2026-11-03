<?php

namespace Fabricate\Schema\V20241105\Capabilities\Resources;

use Fabricate\Contracts\Schema\Capabilities\ResourceDefinition as ResourceDefinitionContract;
use Fabricate\Contracts\Schema\Concerns\Role;
use Fabricate\Schema\V20241105\Concerns\Annotated;

readonly class ResourceDefinition extends Annotated implements ResourceDefinitionContract
{
    /**
     * @param Role[]|null $audience
     */
    public function __construct(
        public string $uri,
        public string $name,
        public ?string $description = null,
        public ?string $mimeType = null,
        public ?int $size = null,
        ?array $audience = null,
        int|float|null $priority = null,
    ) {
        parent::__construct($audience, $priority);
    }

    public function toArray(): array
    {
        $results = parent::toArray();

        $results['uri'] = $this->uri;
        $results['name'] = $this->name;
        if(isset($this->description)) $results['description'] = $this->description;
        if(isset($this->mimeType)) $results['mimeType'] = $this->mimeType;
        if(isset($this->size)) $results['size'] = $this->size;

        return $results;
    }
}
