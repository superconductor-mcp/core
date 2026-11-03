<?php

namespace Fabricate\Schema\V20260728\Messaging\Params;

use Fabricate\Schema\V20260728\Messaging\MetaObjects\NotificationMetaObject;
use Voyager\Contracts\NutsAndBolts\Arrayable;

readonly class NotificationParams implements Arrayable
{
    public function __construct(
        public ?NotificationMetaObject $_meta = null,
    ) {}

    public function toArray(): array
    {
        $results = [];

        if(isset($this->_meta)) $results['_meta'] = $this->_meta->toArray() ?: (object) [];

        return $results;
    }
}
