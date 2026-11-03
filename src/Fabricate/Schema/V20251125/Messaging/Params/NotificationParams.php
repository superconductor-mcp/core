<?php

namespace Fabricate\Schema\V20251125\Messaging\Params;

use Voyager\Contracts\NutsAndBolts\Arrayable;

readonly class NotificationParams implements Arrayable
{
    public function __construct(
        public ?array $_meta = null,
    ) {}

    public function toArray(): array
    {
        $results = [];

        if(isset($this->_meta)) $results['_meta'] = $this->_meta ?: (object) [];

        return $results;
    }
}
