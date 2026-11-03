<?php

namespace Fabricate\Schema\Common\Messaging;

use Fabricate\Contracts\Schema\Messaging\Result as ResultContract;
use Voyager\Contracts\NutsAndBolts\Arrayable;

readonly class Result implements ResultContract
{
    public function __construct(
        public mixed $_meta = null,
    ) {}

    public function toArray(): array
    {
        $results = [];

        if(isset($this->_meta))
        {
            $meta = $this->_meta instanceof Arrayable ? $this->_meta->toArray() : $this->_meta;
            $results['_meta'] = $meta ?: (object) [];
        }

        return $results;
    }
}
