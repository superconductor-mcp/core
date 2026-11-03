<?php

namespace Fabricate\Schema\V20251125;

use Voyager\Contracts\NutsAndBolts\Arrayable;

readonly class TaskMetadata implements Arrayable
{
    public function __construct(
        public ?int $ttl = null
    ) {}

    public function toArray(): array
    {
        $results = [];

        if(isset($this->ttl)) $results['ttl'] = $this->ttl;

        return $results;
    }
}
