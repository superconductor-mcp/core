<?php

namespace Fabricate\Core\Rpc\Procedures\Roots;

use Fabricate\Client\ClientConfiguration;
use Fabricate\Contracts\Mechanism\Attributes\CompatibleVersions;
use Fabricate\Contracts\Mechanism\Attributes\RequiresCapability;
use Fabricate\Contracts\Schema\Messaging\Roots\ListRootsRequest;
use Fabricate\Contracts\Schema\Messaging\Roots\ListRootsResult;
use Fabricate\Core\Rpc\Procedures\ClientCapabilityProcedure;
use Fabricate\Mechanism\Deferred;
use Fabricate\Mechanism\RequestContext;

#[CompatibleVersions('2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25')]
#[RequiresCapability('roots')]
class RootsProcedure extends ClientCapabilityProcedure
{
    /**
     * The roots the client exposes, from its Roots concrete.
     */
    public function handle(ListRootsRequest $request, ClientConfiguration $entity_configuration, string $protocol_version, ?RequestContext $context = null): ListRootsResult|Deferred
    {
        return $this->answer($request, $entity_configuration, $protocol_version, $context);
    }
}
