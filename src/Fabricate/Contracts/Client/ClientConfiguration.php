<?php

namespace Fabricate\Contracts\Client;

use Fabricate\Contracts\Mechanism\EntityConfiguration;
use Fabricate\Contracts\Schema\Messaging\Initialize\InitializeRequest;
use Fabricate\Contracts\Schema\RequestId;

interface ClientConfiguration extends EntityConfiguration
{
    public function initializeRequest(RequestId $id, ?string $protocol_version = null): InitializeRequest;
}
