<?php

namespace Fabricate\Schema\V20260728;

use Voyager\Contracts\NutsAndBolts\Arrayable;

readonly class JSONObject implements Arrayable
{
    /**
     * @param array<string,JSONValue> $properties
     */
    public function __construct(
        public array $properties = [],
    ) {}

    public function toArray(): array
    {
        return array_map(fn(JSONValue $value) => $value->resolve(), $this->properties);
    }
}
