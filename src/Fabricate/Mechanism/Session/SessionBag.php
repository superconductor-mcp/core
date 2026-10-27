<?php

namespace Fabricate\Mechanism\Session;

use Fabricate\NutsAndBolts\RequestIds;
use Fabricate\NutsAndBolts\Messages\Responses\ReceivedResponse;
use Fabricate\NutsAndBolts\MCPServerBag;
use Fabricate\Mechanism\Session\Lifecycle\IdleState;
use Fabricate\Mechanism\Session\Lifecycle\LifecycleState;

// one client's session with a server, whatever carries it: the lifecycle state, the two id ledgers, what is running
class SessionBag extends MCPServerBag
{
    public LifecycleState $state;

    // the spec's id rule is per requestor: the client may not repeat its own ids, the server may not repeat its own
    public RequestIds $their_ids;

    public RequestIds $own_ids;

    /** @var array<string|int, true> ids of client requests still being handled */
    public array $in_flight = [];

    /** @var array<string|int, true> in-flight requests the client cancelled; their responses are not sent */
    public array $cancelled = [];

    /** @var array<string, true> requests this server sent to its client and still waits on, by Session::key() */
    public array $awaiting = [];

    /** @var array<string, ReceivedResponse> the client's answers to them, until whoever waits picks them up */
    public array $answers = [];

    // 2025-06-18 removed JSON-RPC batches; a session that negotiated it gets a batch refused
    public function takesBatches(): bool
    {
        return ($this->protocol_version ?? '2024-11-05') < '2025-06-18';
    }

    public function __construct()
    {
        $this->state = new IdleState();
        $this->their_ids = new RequestIds();
        $this->own_ids = new RequestIds();
    }
}
