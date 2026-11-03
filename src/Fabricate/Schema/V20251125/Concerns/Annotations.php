<?php

namespace Fabricate\Schema\V20251125\Concerns;

use Fabricate\Contracts\Schema\Concerns\Role;
use Voyager\Contracts\NutsAndBolts\Arrayable;

readonly class Annotations implements Arrayable
{
    /**
     * @param Role[]|null $audience
     */
    public function __construct(
        public ?array $audience = null,
        public int|float|null $priority = null,
        public ?string $lastModified = null,
    ) {}

    public function toArray(): array
    {
        $results = [];

        if(isset($this->audience)) $results['audience'] = array_map(fn(Role $role) => $role->value, $this->audience);
        if(isset($this->priority)) $results['priority'] = $this->priority;
        if(isset($this->lastModified)) $results['lastModified'] = $this->lastModified;

        return $results;
    }
}
