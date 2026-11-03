<?php

namespace Fabricate\Schema\V20241105\Concerns;

use Fabricate\Contracts\Schema\Capabilities\EmbeddedResource as EmbeddedContract;
use Fabricate\Contracts\Schema\Concerns\Role;
use Fabricate\Schema\V20241105\Capabilities\Resources\BlobResourceContents;
use Fabricate\Schema\V20241105\Capabilities\Resources\TextResourceContents;

readonly class EmbeddedResource extends Annotated implements EmbeddedContract
{
    public string $type;

    /**
     * @param Role[]|null $audience
     */
    public function __construct(
        public TextResourceContents|BlobResourceContents $resource,
        ?array $audience = null,
        int|float|null $priority = null,
    ) {
        parent::__construct($audience, $priority);

        $this->type = 'resource';
    }

    public function toArray(): array
    {
        $results = parent::toArray();

        $results['type'] = $this->type;
        $results['resource'] = $this->resource->toArray();

        return $results;
    }
}
