<?php

namespace Fabricate\Schema\V20250326\Concerns;

use Voyager\Contracts\NutsAndBolts\Arrayable;

readonly class ResourceReference implements Arrayable
{
    public string $type;

    public function __construct(
        public string $uri,
    ) {
        $this->type = 'ref/resource';
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'uri' => $this->uri,
        ];
    }
}
