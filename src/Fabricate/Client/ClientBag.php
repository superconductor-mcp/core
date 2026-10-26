<?php

namespace Fabricate\Client;

use Fabricate\NutsAndBolts\RequestIds;
use Voyager\Workflows\SharedBag;
use Voyager\Contracts\IOPools\Promise;
use Voyager\Contracts\IOPools\LoopResources\Timer;

class ClientBag extends SharedBag
{
    public ClientStatus $status = ClientStatus::NotConnected;

    // the spec's id rule is per requestor, so each side of this connection has its own ledger
    public RequestIds $own_ids;
    public RequestIds $their_ids;

    /** @var array<string, array{0: Promise, 1: class-string<\Fabricate\NutsAndBolts\Messages\MCPResponse>, 2: string}> requests awaiting a reply, keyed by id, with the response class and method */
    public array $pending = [];

    /** @var array<string, array{0: string, 1: list<array>}> list requests that refill a cache, keyed like pending: the list kind and the pages gathered so far */
    public array $filling = [];

    public ?string $protocol_version = null;
    public array $server_capabilities = [];
    public array $server_info = [];

    // every page of what the server listed last, each entry as the wire array it sent
    public array $tools = [];
    public array $resources = [];
    public array $resource_templates = [];
    public array $prompts = [];

    public ?Timer $connect_deadline = null;

    /** @var array<string, true> list kinds requested right after the handshake and not yet filled; ready resolves when none remain */
    public array $initial_lists = [];

    // why the status is Error
    public ?string $error = null;

    public function __construct()
    {
        $this->own_ids = new RequestIds();
        $this->their_ids = new RequestIds();
    }

    // one key for an int and a string id that print the same, as RequestIds does it
    public static function key(string|int $id): string
    {
        return (is_int($id) ? 'i:' : 's:').$id;
    }
}
