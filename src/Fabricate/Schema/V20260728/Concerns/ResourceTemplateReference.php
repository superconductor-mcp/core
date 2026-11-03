<?php

namespace Fabricate\Schema\V20260728\Concerns;

use Voyager\Contracts\NutsAndBolts\Arrayable;

readonly class ResourceTemplateReference implements Arrayable
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
