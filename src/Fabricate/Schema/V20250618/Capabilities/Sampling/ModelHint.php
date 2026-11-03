<?php

namespace Fabricate\Schema\V20250618\Capabilities\Sampling;

use Voyager\Contracts\NutsAndBolts\Arrayable;

readonly class ModelHint implements Arrayable
{
    public function __construct(
        public ?string $name = null
    ) {}

    public function toArray(): array
    {
        $results = [];

        if(isset($this->name)) $results['name'] = $this->name;

        return $results;
    }
}
