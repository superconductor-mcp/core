<?php

namespace Fabricate\Core\Rpc\Procedures\Logging;

use Fabricate\Contracts\Mechanism\Attributes\CompatibleVersions;
use Fabricate\Contracts\Mechanism\Attributes\RequiresCapability;
use Fabricate\Contracts\Schema\Messaging\Logging\SetLevelRequest;
use Fabricate\Contracts\Schema\Messaging\EmptyResult;
use Fabricate\Contracts\Server\ServerConfiguration;
use Fabricate\Core\Rpc\Procedures\Procedure;
use Fabricate\Schema\Common\Messaging\EmptyResult as SetLevelResult;

#[CompatibleVersions('2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25')]
#[RequiresCapability('logging')]
class LoggingProcedure extends Procedure
{
    public function handle(SetLevelRequest $request, ServerConfiguration $entity_configuration, string $protocol_version): EmptyResult
    {
        $entity_configuration->setLogLevel($request->level());

        return new SetLevelResult();
    }
}
