<?php

namespace Fabricate\Contracts\Mechanism;

use Fabricate\Contracts\Schema\Messaging\Request;

interface EntityConfiguration
{
    public function capabilities(string $protocol_version): EntityCapabilities;

    public function supportedProtocolVersions(): array;

    public function handshakeProtocolVersion(?string $requested_protocol_version = null): string;

    /** @return list<string> */
    public function handshakeProtocolVersions(): array;

    public function acceptsRequest(Request $request): bool;

    /** Whether the configuration names a version, so implementation() can describe it. */
    public function identifiesItself(): bool;
}
