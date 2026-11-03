<?php

namespace Fabricate\Core\Rpc\Procedures\Elicitation;

use Fabricate\Contracts\Client\ClientConfiguration;
use Fabricate\Contracts\Mechanism\Attributes\CompatibleVersions;
use Fabricate\Contracts\Mechanism\Attributes\RequiresCapability;
use Fabricate\Contracts\Schema\Messaging\Elicitation\ElicitRequest;
use Fabricate\Contracts\Schema\Messaging\Elicitation\ElicitResult;
use Fabricate\Contracts\Schema\Messaging\Tasks\CreateTaskResult;
use Fabricate\Core\Rpc\Procedures\Procedure;

#[CompatibleVersions('2025-06-18', '2025-11-25')]
#[RequiresCapability('elicitation')]
class ElicitationProcedure extends Procedure
{
    public function handle(ElicitRequest $request, ClientConfiguration $entity_configuration, string $protocol_version): ElicitResult|CreateTaskResult
    {

    }
}
