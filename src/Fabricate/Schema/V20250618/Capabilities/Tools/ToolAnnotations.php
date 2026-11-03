<?php

namespace Fabricate\Schema\V20250618\Capabilities\Tools;

use Voyager\Contracts\NutsAndBolts\Arrayable;

readonly class ToolAnnotations implements Arrayable
{
    public function __construct(
        public ?string $title = null,
        public ?bool $readOnlyHint = null,
        public ?bool $destructiveHint = null,
        public ?bool $idempotentHint = null,
        public ?bool $openWorldHint = null,
    ) {}

    public function toArray(): array
    {
        $results = [];

        if(isset($this->title)) $results['title'] = $this->title;
        if(isset($this->readOnlyHint)) $results['readOnlyHint'] = $this->readOnlyHint;
        if(isset($this->destructiveHint)) $results['destructiveHint'] = $this->destructiveHint;
        if(isset($this->idempotentHint)) $results['idempotentHint'] = $this->idempotentHint;
        if(isset($this->openWorldHint)) $results['openWorldHint'] = $this->openWorldHint;

        return $results;
    }
}
