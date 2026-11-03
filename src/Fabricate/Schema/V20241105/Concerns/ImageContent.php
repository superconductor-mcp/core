<?php

namespace Fabricate\Schema\V20241105\Concerns;

use Fabricate\Contracts\Schema\Concerns\Role;
use Fabricate\Contracts\Schema\Contracts\ImageContent as ImageContract;

readonly class ImageContent extends Annotated implements ImageContract
{
    public string $type;

    /**
     * @param Role[]|null $audience
     */
    public function __construct(
        public string $data,
        public string $mimeType,
        ?array $audience = null,
        int|float|null $priority = null,
    ) {
        parent::__construct($audience, $priority);

        $this->type = 'image';
    }

    public function toArray(): array
    {
        $results = parent::toArray();

        $results['type'] = $this->type;
        $results['data'] = $this->data;
        $results['mimeType'] = $this->mimeType;

        return $results;
    }
}
