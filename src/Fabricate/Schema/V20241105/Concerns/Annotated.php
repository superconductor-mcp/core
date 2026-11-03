<?php

namespace Fabricate\Schema\V20241105\Concerns;

use Fabricate\Contracts\Schema\Concerns\Role;
use Fabricate\Contracts\Schema\Contracts\Annotated as AnnotatedContract;

abstract readonly class Annotated implements AnnotatedContract
{
    public ?array $annotations;

    /**
     * @param Role[]|null $audience
     */
    public function __construct(
        ?array $audience = null,
        int|float|null $priority = null,
    ) {
        if(is_null($audience) && is_null($priority)) $this->annotations = null;
        else
        {
            $payload = [];
            if(!is_null($audience)) $payload['audience'] = array_map(fn(Role $role) => $role->value, $audience);
            if(!is_null($priority)) $payload['priority'] = $priority;

            $this->annotations = $payload;
        }
    }

    public function toArray(): array
    {
        $results = [];

        if(isset($this->annotations)) $results['annotations'] = $this->annotations;

        return $results;
    }
}
