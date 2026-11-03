<?php

namespace Fabricate\Core\Rpc\Procedures\Sampling;

use Fabricate\Contracts\Client\ClientConfiguration;
use Fabricate\Contracts\Mechanism\Attributes\CompatibleVersions;
use Fabricate\Contracts\Mechanism\Attributes\RequiresCapability;
use Fabricate\Contracts\Schema\Messaging\Sampling\CreateMessageRequest;
use Fabricate\Contracts\Schema\Messaging\Sampling\CreateMessageResult;
use Fabricate\Contracts\Schema\Messaging\Tasks\CreateTaskResult;
use Fabricate\Core\Rpc\Procedures\Procedure;

#[CompatibleVersions('2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25')]
#[RequiresCapability('sampling')]
class SamplingProcedure extends Procedure
{
    public function handle(CreateMessageRequest $request, ClientConfiguration $entity_configuration, string $protocol_version): CreateMessageResult|CreateTaskResult
    {

    }
}
