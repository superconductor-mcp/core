<?php

namespace Fabricate\Schema\Common\Concerns;

readonly class BaseMetaData
{

    public function __construct(
        public string $name,
        public ?string $title = null,

    ) {}

    public function toArray(): array
    {
        $results = ['name' => $this->name];

        if(isset($this->title)) $results['title'] = $this->title;

        return $results;
    }
}
