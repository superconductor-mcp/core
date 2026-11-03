<?php

namespace Fabricate\Core\Rpc\Procedures\Server;

use Fabricate\Contracts\Mechanism\Attributes\CompatibleVersions;
use Fabricate\Contracts\Schema\Messaging\Discover\DiscoverRequest;
use Fabricate\Contracts\Schema\Messaging\Discover\DiscoverResult;
use Fabricate\Contracts\Server\ServerConfiguration;
use Fabricate\Core\Rpc\Procedures\Procedure;
use Fabricate\Schema\V20260728;

#[CompatibleVersions('2026-07-28')]
class DiscoverProcedure extends Procedure
{
    /**
     * The modern protocol versions the server answers, its capabilities, its identity in _meta, and its instructions.
     * Not cached: a client asking again gets what the server offers now.
     */
    public function handle(DiscoverRequest $request, ServerConfiguration $entity_configuration, string $protocol_version): DiscoverResult
    {
        return new V20260728\Messaging\Discover\DiscoverResult(
            array_values(array_intersect($entity_configuration->supportedProtocolVersions(), ['2026-07-28'])),
            $entity_configuration->capabilities($protocol_version),
            0,
            V20260728\Concerns\CacheScope::PRIVATE,
            instructions: $entity_configuration->instructions(),
            _meta: $this->resultMeta($entity_configuration, $protocol_version),
        );
    }
}
