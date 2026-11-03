<?php

namespace Fabricate\Core\Rpc\Procedures\Ping;

use Fabricate\Core\Rpc\Procedures\Procedure;
use Fabricate\Contracts\Schema\Messaging\EmptyResult;
use Fabricate\Contracts\Mechanism\EntityConfiguration;
use Fabricate\Contracts\Schema\Messaging\Ping\PingRequest;
use Fabricate\Contracts\Mechanism\Attributes\CompatibleVersions;
use Fabricate\Schema\Common\Messaging\EmptyResult as PingResult;

#[CompatibleVersions('2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25')]
class PingProcedure extends Procedure
{
    public function handle(PingRequest $request, EntityConfiguration $entity_configuration, string $protocol_version): EmptyResult
    {
        return new PingResult();
    }
}
