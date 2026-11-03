<?php

namespace Fabricate\Client;

use Fabricate\Client\Signals\RootsChanged;
use Fabricate\Mechanism\Concerns\RunsAsync;

/**
 * The roots a client exposes, as the developer defines them. A concrete implements handle(), which the container calls
 * with the server's roots/list request, the client configuration, the protocol_version and, when it asks for one, the
 * RequestContext. It returns that version's ListRootsResult, or, async, hands it to finish().
 */
abstract class Roots
{
    use RunsAsync;

    /**
     * Tells every open client the roots changed; each that declared roots.listChanged sends
     * notifications/roots/list_changed.
     */
    public static function changed(): void
    {
        signal(new RootsChanged());
    }
}
