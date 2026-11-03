<?php

namespace Fabricate\Core\Rpc\Procedures\Elicitation;

use Fabricate\Client\ClientConfiguration;
use Fabricate\Contracts\Mechanism\Attributes\CompatibleVersions;
use Fabricate\Contracts\Mechanism\Attributes\RequiresCapability;
use Fabricate\Contracts\Schema\Messaging\Elicitation\ElicitRequest;
use Fabricate\Contracts\Schema\Messaging\Elicitation\ElicitResult;
use Fabricate\Contracts\Schema\Messaging\Tasks\CreateTaskResult;
use Fabricate\Core\Rpc\Procedures\ClientCapabilityProcedure;
use Fabricate\Mechanism\Deferred;
use Fabricate\Mechanism\RequestContext;

#[CompatibleVersions('2025-06-18', '2025-11-25')]
#[RequiresCapability('elicitation')]
class ElicitationProcedure extends ClientCapabilityProcedure
{
    /**
     * The user's answer, from the client's Elicitation concrete, in a mode the client declared; on 2025-11-25 as a task when asked for and offered.
     */
    public function handle(ElicitRequest $request, ClientConfiguration $entity_configuration, string $protocol_version, ?RequestContext $context = null): ElicitResult|CreateTaskResult|Deferred
    {
        return $this->answer($request, $entity_configuration, $protocol_version, $context);
    }
}
