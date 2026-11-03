<?php

use Fabricate\Client\ClientCapabilitiesFactory;
use Fabricate\Client\ClientConfiguration;
use Fabricate\Client\MCPClient;
use Fabricate\Contracts\Mechanism\Attributes\ClientConfig;
use Fabricate\Contracts\Schema\RequestId;
use Fabricate\Core\Routing\ProcedureRouter;
use Fabricate\Mechanism\MessageDecoder;
use Fabricate\Mechanism\Signals\RequestTimedOut;
use Fabricate\Schema\V20241105;
use Fabricate\Schema\V20250326;
use Fabricate\Schema\V20250618;
use Fabricate\Schema\V20251125;
use Fabricate\Schema\V20260728;
use Voyager\Config\Repository;
use Voyager\Contracts\IOPools\Loop;
use Voyager\Contracts\IOPools\LoopResources\Timer;
use Voyager\Signals\SignalDispatcher;
use Voyager\Vessel\ControlPanel;

#[ClientConfig('patient', 'Waits for answers.', '1.0.0')]
class PatientClient extends ClientConfiguration
{
    protected array $supported_protocol_versions = ['2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25', '2026-07-28'];

    protected ?float $request_timeout = 5;
}

beforeEach(function () {
    ControlPanel::setInstance($panel = new ControlPanel());
    $panel->registerInstance('config', new Repository(['mcp' => require __DIR__.'/../../../config/mcp.php']));
    $panel->registerSingleton(ProcedureRouter::class);
    $panel->registerInstance('signals', $signals = new SignalDispatcher($panel));

    // Timers the entity starts, kept to be fired by hand, each knowing whether it was cancelled.
    $this->timers = new ArrayObject();
    $loop = $this->createMock(Loop::class);
    $loop->method('at')->willReturnCallback(function(float $seconds, callable $fire) {
        $timer = new class($seconds, $fire) implements Timer {
            public bool $cancelled = false;
            public function __construct(public float $seconds, public $fire) {}
            public function cancel(): void { $this->cancelled = true; }
            public function cancelled(): bool { return $this->cancelled; }
            public function interval(): ?int { return null; }
            public function fire(): void { ($this->fire)(); }
            public function dueAt(): ?int { return null; }
        };
        $this->timers->append($timer);

        return $timer;
    });
    $panel->registerInstance(Loop::class, $loop);

    $this->timedOut = new ArrayObject();
    $signals->listen(RequestTimedOut::class, fn(RequestTimedOut $signal) => $this->timedOut->append($signal));

    require __DIR__.'/../../../routes/mcp.php';

    $this->configuration = new PatientClient(new ClientCapabilitiesFactory());
});

afterEach(fn() => ControlPanel::setInstance(null));

/**
 * A tools/list in the version, with its id.
 */
function listIn(string $version, ClientConfiguration $configuration): object
{
    $id = new RequestId(7);

    return match($version) {
        '2024-11-05' => new V20241105\Messaging\Tools\ListToolsRequest(),
        '2025-03-26' => new V20250326\Messaging\Tools\ListToolsRequest(),
        '2025-06-18' => new V20250618\Messaging\Tools\ListToolsRequest(),
        '2025-11-25' => new V20251125\Messaging\Tools\ListToolsRequest($id),
        '2026-07-28' => new V20260728\Messaging\Tools\ListToolsRequest($id, new V20260728\Messaging\Params\PaginatedRequestParams(new V20260728\Messaging\MetaObjects\RequestMetaObject('2026-07-28', $configuration->capabilities('2026-07-28')))),
    };
}

it('cancels a request that outlives the timeout, stops waiting for it, and says so.', function (string $version) {
    $client = new MCPClient($this->configuration, $version);
    $answered = false;

    $client->request(listIn($version, $this->configuration), new RequestId(7), function() use (&$answered) { $answered = true; });
    $client->pullQueued();

    expect($this->timers[0]->seconds)->toBe(5.0);

    ($this->timers[0]->fire)();
    $cancel = json_decode($client->pullQueued()[0]->toJson(), true);

    // A late answer has nothing waiting on it.
    $client->receiveMessage(MessageDecoder::fromWire('{"jsonrpc":"2.0","id":7,"result":{"tools":[]}}'));

    expect($cancel['method'])->toBe('notifications/cancelled')
        ->and($cancel['params']['requestId'])->toBe(7)
        ->and($this->timedOut)->toHaveCount(1)
        ->and($this->timedOut[0]->method)->toBe('tools/list')
        ->and($answered)->toBeFalse();
})->with(['2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25', '2026-07-28']);

it('stops the timeout of a request answered in time.', function () {
    $client = new MCPClient($this->configuration, '2025-06-18');
    $client->request(listIn('2025-06-18', $this->configuration), new RequestId(7));

    $client->receiveMessage(MessageDecoder::fromWire('{"jsonrpc":"2.0","id":7,"result":{"tools":[]}}'));

    expect($this->timers[0]->cancelled)->toBeTrue();
});

it('never times out subscriptions/listen, which stays open until cancelled.', function () {
    $client = new MCPClient($this->configuration, '2026-07-28');
    $meta = new V20260728\Messaging\MetaObjects\RequestMetaObject('2026-07-28', $this->configuration->capabilities('2026-07-28'));
    $client->request(new V20260728\Messaging\Resources\SubscriptionsListenRequest(new RequestId(9), new V20260728\Messaging\Params\SubscriptionsListenRequestParams(new V20260728\Capabilities\Resources\SubscriptionFilter(toolsListChanged: true), $meta)));

    expect($this->timers)->toHaveCount(0);
});

it('stops every timeout when the client closes.', function () {
    $client = new MCPClient($this->configuration, '2025-06-18');
    $client->request(listIn('2025-06-18', $this->configuration), new RequestId(7));
    $client->close();

    expect($this->timers[0]->cancelled)->toBeTrue();
});
