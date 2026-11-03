<?php

namespace Fabricate\Schema\V20260728;

use Voyager\Contracts\NutsAndBolts\Arrayable;

readonly class MetaObject implements Arrayable
{
    /**
     * @param array<string,mixed> $record
     */
    public function __construct(
        public array $record = []
    ) {}

    public function toArray(): array
    {
        return $this->record;
    }
}
