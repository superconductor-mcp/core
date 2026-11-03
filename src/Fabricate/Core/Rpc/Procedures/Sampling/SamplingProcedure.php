<?php

namespace Fabricate\Core\Rpc\Procedures\Sampling;

use Fabricate\Client\ClientConfiguration;
use Fabricate\Contracts\Mechanism\Attributes\CompatibleVersions;
use Fabricate\Contracts\Mechanism\Attributes\RequiresCapability;
use Fabricate\Contracts\Schema\Messaging\Sampling\CreateMessageRequest;
use Fabricate\Contracts\Schema\Messaging\Sampling\CreateMessageResult;
use Fabricate\Contracts\Schema\Messaging\Tasks\CreateTaskResult;
use Fabricate\Core\Rpc\Procedures\ClientCapabilityProcedure;
use Fabricate\Mechanism\Deferred;
use Fabricate\Mechanism\RequestContext;

#[CompatibleVersions('2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25')]
#[RequiresCapability('sampling')]
class SamplingProcedure extends ClientCapabilityProcedure
{
    /**
     * A model sample, from the client's Sampling concrete; on 2025-11-25 as a task when asked for and offered.
     */
    public function handle(CreateMessageRequest $request, ClientConfiguration $entity_configuration, string $protocol_version, ?RequestContext $context = null): CreateMessageResult|CreateTaskResult|Deferred
    {
        return $this->answer($request, $entity_configuration, $protocol_version, $context);
    }
}
