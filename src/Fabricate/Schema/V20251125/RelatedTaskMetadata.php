<?php

namespace Fabricate\Schema\V20251125;

use Voyager\Contracts\NutsAndBolts\Arrayable;

readonly class RelatedTaskMetadata implements Arrayable
{
    public function __construct(
        public string $taskId,
    ) {}

    public function toArray(): array
    {
        return ['taskId' => $this->taskId];
    }
}
