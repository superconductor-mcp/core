<?php

namespace Fabricate\Schema\V20251125\Capabilities\Tools;

use Fabricate\Schema\V20251125\Concerns\TaskSupport;
use Voyager\Contracts\NutsAndBolts\Arrayable;

readonly class ToolExecution implements Arrayable
{
    public function __construct(
        public ?TaskSupport $taskSupport = null,
    ) {}

    public function toArray(): array
    {
        $results = [];

        if(isset($this->taskSupport)) $results['taskSupport'] = $this->taskSupport->value;

        return $results;
    }
}
