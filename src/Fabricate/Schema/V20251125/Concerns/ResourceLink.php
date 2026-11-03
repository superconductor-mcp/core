<?php

namespace Fabricate\Schema\V20251125\Concerns;

use Fabricate\Schema\Common\Concerns\Icon;
use Fabricate\Schema\V20251125\Capabilities\Resources\ResourceDefinition;

readonly class ResourceLink extends ResourceDefinition implements ContentBlock
{
    public string $type;

    /**
     * @param Icon[]|null $icons
     */
    public function __construct(
        string $uri,
        string $name,
        ?string $description = null,
        ?string $mimeType = null,
        ?Annotations $annotations = null,
        ?int $size = null,
        ?array $icons = null,
        ?string $title = null,
        ?array $_meta = null,
    ) {
        parent::__construct($uri, $name, $description, $mimeType, $annotations, $size, $icons, $title, $_meta);

        $this->type = 'resource_link';
    }

    public function toArray(): array
    {
        $results = parent::toArray();

        $results['type'] = $this->type;

        return $results;
    }
}
