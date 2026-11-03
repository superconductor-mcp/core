<?php

namespace Fabricate\Schema\Common\Concerns;

use Voyager\Contracts\NutsAndBolts\Arrayable;

trait HasCapabilityPayloads
{
    /**
     * Capability payloads are objects at every depth, so an empty one has to encode as {} rather than [].
     */
    protected function payload(array $payload): array|object
    {
        return array_map(function(mixed $value) {
            if($value instanceof Arrayable) $value = $value->toArray();

            return is_array($value) ? $this->payload($value) : $value;
        }, $payload) ?: (object) [];
    }
}
