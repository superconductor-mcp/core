<?php

namespace Fabricate\Mechanism;

use Ramsey\Uuid\Uuid;

class SerializableSession
{
    protected string $uuid;

    public function __construct() {
        $this->uuid = Uuid::uuid4()->toString();
    }
}
