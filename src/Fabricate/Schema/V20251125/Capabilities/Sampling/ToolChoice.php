<?php

namespace Fabricate\Schema\V20251125\Capabilities\Sampling;

use Fabricate\Contracts\Schema\ToolChoiceMode;
use Voyager\Contracts\NutsAndBolts\Arrayable;

readonly class ToolChoice implements Arrayable
{
    public function __construct(
        public ?ToolChoiceMode $mode = null,
    ) {}

    public function toArray(): array
    {
        $results = [];

        if(isset($this->mode)) $results['mode'] = $this->mode->value;

        return $results;
    }
}
