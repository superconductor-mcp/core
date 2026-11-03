<?php

namespace App\Mcp\Roots;

use Fabricate\Client\Roots;
use Fabricate\Contracts\Schema\Messaging\Roots\ListRootsResult;
use Fabricate\Schema\V20241105;
use Fabricate\Schema\V20250326;
use Fabricate\Schema\V20250618;
use Fabricate\Schema\V20251125;
use Fabricate\Schema\V20260728;

/**
 * The devbox app's own directory, as the one root CuriousClient exposes.
 */
class CuriousRoots extends Roots
{
    public function handle(string $protocol_version): ListRootsResult
    {
        [$uri, $name] = ['file://'.base_path(), 'devbox'];

        return match($protocol_version) {
            '2024-11-05' => new V20241105\Messaging\Roots\ListRootsResult([new V20241105\Capabilities\Roots\Root($uri, $name)]),
            '2025-03-26' => new V20250326\Messaging\Roots\ListRootsResult([new V20250326\Capabilities\Roots\Root($uri, $name)]),
            '2025-06-18' => new V20250618\Messaging\Roots\ListRootsResult([new V20250618\Capabilities\Roots\Root($uri, $name)]),
            '2025-11-25' => new V20251125\Messaging\Roots\ListRootsResult([new V20251125\Capabilities\Roots\Root($uri, $name)]),
            '2026-07-28' => new V20260728\Messaging\Roots\ListRootsResult([new V20260728\Capabilities\Roots\Root($uri, $name)]),
        };
    }
}
