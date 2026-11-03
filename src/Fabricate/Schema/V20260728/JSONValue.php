<?php

namespace Fabricate\Schema\V20260728;

readonly class JSONValue
{
    public function __construct(
        public string|int|float|bool|null|JSONObject|JSONArray $value = null
    ) {}

    public function resolve(): mixed
    {
        if($this->value instanceof JSONObject) return $this->value->toArray() ?: (object) [];
        if($this->value instanceof JSONArray) return $this->value->toArray();

        return $this->value;
    }
}
