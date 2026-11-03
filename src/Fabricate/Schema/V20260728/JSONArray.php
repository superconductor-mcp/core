<?php

namespace Fabricate\Schema\V20260728;

use Voyager\Contracts\NutsAndBolts\Arrayable;

readonly class JSONArray implements Arrayable
{
    /**
     * @param JSONValue[] $values
     */
    public function __construct(
        public array $values = []
    ) {}

    public function toArray(): array
    {
        return array_map(fn(JSONValue $value) => $value->resolve(), array_values($this->values));
    }
}
