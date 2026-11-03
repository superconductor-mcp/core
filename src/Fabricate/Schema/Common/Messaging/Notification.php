<?php

namespace Fabricate\Schema\Common\Messaging;

use Fabricate\Contracts\Schema\Messaging\Notification as NotificationContract;
use Voyager\Contracts\NutsAndBolts\Arrayable;

abstract readonly class Notification implements NotificationContract
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
