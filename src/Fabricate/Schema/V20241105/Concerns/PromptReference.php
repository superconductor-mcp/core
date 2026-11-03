<?php

namespace Fabricate\Schema\V20241105\Concerns;

use Voyager\Contracts\NutsAndBolts\Arrayable;

readonly class PromptReference implements Arrayable
{
    public string $type;

    public function __construct(
        public string $name,
    ) {
        $this->type = 'ref/prompt';
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'name' => $this->name,
        ];
    }
}
