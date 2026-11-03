<?php

use Fabricate\Client\ClientCapabilitiesFactory;
use Fabricate\Client\ClientConfiguration;
use Fabricate\Client\MCPClient;
use Fabricate\Client\Signals\NotificationReceived;
use Fabricate\Client\Signals\ResponseReceived;
use Fabricate\Contracts\Mechanism\Attributes\ClientConfig;
use Fabricate\Contracts\Schema\Messaging\MCPError;
use Fabricate\Contracts\Schema\Messaging\Tools\CallToolResult;
use Fabricate\Contracts\Schema\RequestId;
use Fabricate\Core\Routing\ProcedureRouter;
use Fabricate\Mechanism\MessageDecoder;
use Fabricate\Schema\V20241105;
use Fabricate\Schema\V20250326;
use Fabricate\Schema\V20250618;
use Fabricate\Schema\V20251125;
use Fabricate\Schema\V20260728;
use Voyager\Config\Repository;
use Voyager\Signals\SignalDispatcher;
use Voyager\Vessel\ControlPanel;

#[ClientConfig('tester', 'Talks to servers.', '1.0.0')]
class TesterClient extends ClientConfiguration
{
    protected array $supported_protocol_versions = ['2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25', '2026-07-28'];
}

beforeEach(function () {
    ControlPanel::setInstance($panel = new ControlPanel());
    $panel->registerInstance('config', new Repository(['mcp' => require __DIR__.'/../../../config/mcp.php']));
    $panel->registerSingleton(ProcedureRouter::class);
    $panel->registerInstance('signals', $signals = new SignalDispatcher($panel));

    $this->signalled = new ArrayObject();
    $signals->listen(NotificationReceived::class, fn(NotificationReceived $s) => $this->signalled->append($s));
    $signals->listen(ResponseReceived::class, fn(ResponseReceived $s) => $this->signalled->append($s));

    require __DIR__.'/../../../routes/mcp.php';

    $this->configuration = new TesterClient(new ClientCapabilitiesFactory());
});

afterEach(fn() => ControlPanel::setInstance(null));

function receives(MCPClient $client, string $wire): ?array
{
    $reply = $client->receiveMessage(MessageDecoder::fromWire($wire));

    return $reply ? json_decode($reply->toJson(), true) : null;
}

function queued(MCPClient $client): array
{
    return array_map(fn($m) => json_decode($m->toJson(), true), $client->pullQueued());
}

it('queues a request with the envelope id before 2025-11-25, and the request\'s own id from it.', function () {
    $early = new MCPClient($this->configuration, '2025-06-18');
    $early->request(new V20250618\Messaging\Tools\CallToolRequest('echo', ['message' => 'hi']), new RequestId(4));

    $late = new MCPClient($this->configuration, '2025-11-25');
    $late->request(new V20251125\Messaging\Tools\CallToolRequest(new RequestId(5), new V20251125\Messaging\Params\CallToolRequestParams('echo', ['message' => 'hi'])));

    expect(queued($early))->toBe([['jsonrpc' => '2.0', 'id' => 4, 'method' => 'tools/call', 'params' => ['name' => 'echo', 'arguments' => ['message' => 'hi']]]])
        ->and(queued($late)[0])->toMatchArray(['id' => 5, 'method' => 'tools/call']);
});

it('refuses to send an early-version request without an id.', function () {
    expect(fn() => new MCPClient($this->configuration, '2025-06-18')->request(new V20250618\Messaging\Ping\PingRequest()))->toThrow(InvalidArgumentException::class);
});

it('hands a response to the closure that sent the request, typed for the method.', function () {
    $client = new MCPClient($this->configuration, '2025-06-18');
    $got = null;
    $client->request(new V20250618\Messaging\Tools\CallToolRequest('echo', ['message' => 'hi']), new RequestId(4), function($r) use (&$got) { $got = $r; });

    receives($client, '{"jsonrpc":"2.0","id":4,"result":{"content":[{"type":"text","text":"hi"}]}}');

    expect($got)->toBeInstanceOf(CallToolResult::class)
        ->and($got->toArray()['content'][0]['text'])->toBe('hi')
        ->and($this->signalled)->toHaveCount(0);
});

it('signals a response nothing waits on, with the method it answers, and an error as an MCPError.', function () {
    $client = new MCPClient($this->configuration, '2025-06-18');
    $client->request(new V20250618\Messaging\Ping\PingRequest(), new RequestId(1));
    $client->request(new V20250618\Messaging\Ping\PingRequest(), new RequestId(2));

    receives($client, '{"jsonrpc":"2.0","id":1,"result":{}}');
    receives($client, '{"jsonrpc":"2.0","id":2,"error":{"code":-32603,"message":"boom"}}');

    expect($this->signalled[0])->toBeInstanceOf(ResponseReceived::class)
        ->and($this->signalled[0]->method)->toBe('ping')
        ->and($this->signalled[0]->id->id)->toBe(1)
        ->and($this->signalled[1]->response)->toBeInstanceOf(MCPError::class)
        ->and($this->signalled[1]->response->toArray()['message'])->toBe('boom');
});

it('ignores a response to no request of its own.', function () {
    $client = new MCPClient($this->configuration, '2025-06-18');

    expect(receives($client, '{"jsonrpc":"2.0","id":9,"result":{}}'))->toBeNull()
        ->and($this->signalled)->toHaveCount(0);
});

it('answers a server request with its procedure\'s result, in the request id.', function (string $version) {
    $client = new MCPClient($this->configuration, $version);

    expect(receives($client, '{"jsonrpc":"2.0","id":3,"method":"ping"}'))->toBe(['jsonrpc' => '2.0', 'id' => 3, 'result' => []])
        ->and(receives($client, '{"jsonrpc":"2.0","id":5,"method":"tools/list"}')['error']['code'])->toBe(-32601);
})->with(['2024-11-05', '2025-06-18', '2025-11-25']);

it('signals every server notification, and queues a notification it sends.', function () {
    $client = new MCPClient($this->configuration, '2025-06-18');

    expect(receives($client, '{"jsonrpc":"2.0","method":"notifications/tools/list_changed"}'))->toBeNull()
        ->and($this->signalled[0])->toBeInstanceOf(NotificationReceived::class)
        ->and($this->signalled[0]->notification->method)->toBe('notifications/tools/list_changed');

    $client->notify(new V20250618\Messaging\Roots\RootsListChangedNotification());

    expect(queued($client))->toBe([['jsonrpc' => '2.0', 'method' => 'notifications/roots/list_changed']]);
});

it('sends a 2026-07-28 request as built, with its _meta, and types its response.', function () {
    $client = new MCPClient($this->configuration, '2026-07-28');
    $meta = new V20260728\Messaging\MetaObjects\RequestMetaObject('2026-07-28', $this->configuration->capabilities('2026-07-28'), $this->configuration->implementation('2026-07-28'));
    $got = null;
    $client->request(new V20260728\Messaging\Tools\CallToolRequest(new RequestId(1), new V20260728\Messaging\Params\CallToolRequestParams('echo', $meta, ['message' => 'hi'])), then: function($r) use (&$got) { $got = $r; });

    $sent = queued($client)[0];
    receives($client, '{"jsonrpc":"2.0","id":1,"result":{"resultType":"complete","content":[{"type":"text","text":"hi"}]}}');

    expect($sent['params']['_meta']['io.modelcontextprotocol/protocolVersion'])->toBe('2026-07-28')
        ->and($sent['params']['_meta']['io.modelcontextprotocol/clientInfo']['name'])->toBe('tester')
        ->and($got)->toBeInstanceOf(CallToolResult::class);
});

it('answers every request still in flight with an error when it closes, saying why.', function () {
    $client = new MCPClient($this->configuration, '2025-06-18');
    $got = null;
    $client->request(new V20250618\Messaging\Ping\PingRequest(), new RequestId(1), function($r) use (&$got) { $got = $r; });
    $client->request(new V20250618\Messaging\Ping\PingRequest(), new RequestId(2));

    $client->close('The server went away.');

    expect($got)->toBeInstanceOf(MCPError::class)
        ->and($got->toArray()['message'])->toBe('The server went away.')
        ->and($this->signalled)->toHaveCount(1)
        ->and($this->signalled[0])->toBeInstanceOf(ResponseReceived::class)
        ->and($this->signalled[0]->id->id)->toBe(2)
        ->and($this->signalled[0]->method)->toBe('ping')
        ->and($this->signalled[0]->response->toArray()['message'])->toBe('The server went away.');

    // A late answer finds nothing waiting.
    receives($client, '{"jsonrpc":"2.0","id":1,"result":{}}');

    expect($this->signalled)->toHaveCount(1);
});

it('answers a request sent after it closed with the close reason at once, without queuing it.', function () {
    $client = new MCPClient($this->configuration, '2025-06-18');
    $client->close('The server went away.');

    $got = null;
    $client->request(new V20250618\Messaging\Ping\PingRequest(), new RequestId(1), function($r) use (&$got) { $got = $r; });
    $client->request(new V20250618\Messaging\Ping\PingRequest(), new RequestId(2));

    expect($got)->toBeInstanceOf(MCPError::class)
        ->and($got->toArray()['message'])->toBe('The server went away.')
        ->and($client->pullQueued())->toBe([])
        ->and($this->signalled)->toHaveCount(1)
        ->and($this->signalled[0]->id->id)->toBe(2)
        ->and($this->signalled[0]->response->toArray()['message'])->toBe('The server went away.');
});

it('builds the error for a failed request in the session version\'s class.', function (string $version, string $class) {
    $client = new MCPClient($this->configuration, $version);
    $got = null;
    $client->request(match($version) {
        '2024-11-05' => new V20241105\Messaging\Ping\PingRequest(),
        '2025-03-26' => new V20250326\Messaging\Ping\PingRequest(),
        '2025-06-18' => new V20250618\Messaging\Ping\PingRequest(),
        '2025-11-25' => new V20251125\Messaging\Ping\PingRequest(new RequestId(1)),
        '2026-07-28' => new V20260728\Messaging\Tools\ListToolsRequest(new RequestId(1), new V20260728\Messaging\Params\PaginatedRequestParams(new V20260728\Messaging\MetaObjects\RequestMetaObject('2026-07-28', $this->configuration->capabilities('2026-07-28')))),
    }, new RequestId(1), function($r) use (&$got) { $got = $r; });

    $client->fail(new RequestId(1), 'Lost.');

    expect($got)->toBeInstanceOf($class)->and($got->toArray()['code'])->toBe(-32603);
})->with([
    ['2024-11-05', V20241105\Messaging\MCPError::class],
    ['2025-03-26', V20250326\Messaging\MCPError::class],
    ['2025-06-18', V20250618\Messaging\MCPError::class],
    ['2025-11-25', V20251125\Messaging\MCPError::class],
    ['2026-07-28', V20260728\Messaging\MCPError::class],
]);
