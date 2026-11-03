<?php

namespace Fabricate\Client;

use Fabricate\Mechanism\Concerns\RunsAsync;

/**
 * How a client samples a model for a server, as the developer connects it: any model, through any API or bridge. A
 * concrete implements handle(), which the container calls with the server's sampling/createMessage request, the client
 * configuration, the protocol_version and, when it asks for one, the RequestContext. It returns that version's
 * CreateMessageResult, or, async, hands it to finish(). A user who rejects the request is refused by throwing a
 * SuperconductorMCPException with SamplingErrorCode::USER_REJECTED.
 */
abstract class Sampling
{
    use RunsAsync;
}
