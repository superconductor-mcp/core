<?php

namespace Fabricate\Schema\Common\Messaging;

use Fabricate\Contracts\Schema\Messaging\Request as RequestContract;
use Voyager\Contracts\NutsAndBolts\Arrayable;

abstract readonly class Request implements RequestContract
{
    public function __construct(
        public string $method,
        public mixed $params = null
    ) {}

    public function toArray(): array
    {
        $results = ['method' => $this->method];

        if(isset($this->params))
        {
            $params = $this->params instanceof Arrayable ? $this->params->toArray() : $this->params;
            $results['params'] = $params ?: (object) [];
        }

        return $results;
    }
}
