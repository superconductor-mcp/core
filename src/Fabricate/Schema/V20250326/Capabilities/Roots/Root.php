<?php

namespace Fabricate\Schema\V20250326\Capabilities\Roots;

use Fabricate\Contracts\Schema\Capabilities\Root as RootContract;

readonly class Root implements RootContract
{
    public function __construct(
        public string  $uri,
        public ?string $name = null,
    ) {}

    public function toArray(): array
    {
        $results = ['uri' => $this->uri];

        if(isset($this->name)) $results['name'] = $this->name;

        return $results;
    }
}
