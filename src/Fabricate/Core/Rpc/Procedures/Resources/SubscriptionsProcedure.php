<?php

namespace Fabricate\Core\Rpc\Procedures\Resources;

use Fabricate\Contracts\Mechanism\Attributes\CompatibleVersions;
use Fabricate\Contracts\Schema\Messaging\Resources\SubscriptionsListenRequest;
use Fabricate\Contracts\Schema\Messaging\Resources\SubscriptionsListenResult;
use Fabricate\Contracts\Server\ServerConfiguration;
use Fabricate\Core\Rpc\Procedures\Procedure;

#[CompatibleVersions('2026-07-28')]
class SubscriptionsProcedure extends Procedure
{
    public function handle(SubscriptionsListenRequest $request, ServerConfiguration $entity_configuration, string $protocol_version): SubscriptionsListenResult
    {

    }
}
