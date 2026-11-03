<?php

namespace Fabricate\Client;

use Fabricate\Mechanism\Concerns\RunsAsync;

/**
 * How a client asks its user for what a server requests, as the developer presents it. A concrete implements handle(),
 * which the container calls with the server's elicitation/create request, the client configuration, the
 * protocol_version and, when it asks for one, the RequestContext. It returns that version's ElicitResult, or, async,
 * hands it to finish(). Only modes the client declared reach it.
 */
abstract class Elicitation
{
    use RunsAsync;
}
