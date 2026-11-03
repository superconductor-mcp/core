<?php

namespace Fabricate\Schema\V20260728\Capabilities\Sampling;

use Voyager\Contracts\NutsAndBolts\Arrayable;

readonly class ModelPreferences implements Arrayable
{
    /**
     * @param ModelHint[]|null $hints
     * @param int|float|null $costPriority
     * @param int|float|null $speedPriority
     * @param int|float|null $intelligencePriority
     */
    public function __construct(
        public ?array $hints = null,
        public int|float|null $costPriority = null,
        public int|float|null $speedPriority = null,
        public int|float|null $intelligencePriority = null,
    ) {}

    public function toArray(): array
    {
        $results = [];

        if(isset($this->hints)) $results['hints'] = array_map(fn(ModelHint $hint) => $hint->toArray() ?: (object) [], $this->hints);
        if(isset($this->costPriority)) $results['costPriority'] = $this->costPriority;
        if(isset($this->speedPriority)) $results['speedPriority'] = $this->speedPriority;
        if(isset($this->intelligencePriority)) $results['intelligencePriority'] = $this->intelligencePriority;

        return $results;
    }
}
