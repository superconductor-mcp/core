<?php

use Fabricate\Server\MCPServer;
use Fabricate\NutsAndBolts\LineDecoder;
use Fabricate\NutsAndBolts\Capabilities;
use Fabricate\NutsAndBolts\PromptDefinition;
use Fabricate\NutsAndBolts\ToolDefinition;
use Fabricate\NutsAndBolts\Schema\TextProperty;
use Fabricate\NutsAndBolts\Schema\ObjectSchema;
use Fabricate\NutsAndBolts\Schema\StringProperty;
use Fabricate\Mechanism\Session\Session;
use Fabricate\Mechanism\Session\SessionBag;
use Fabricate\Mechanism\Session\Elicitation;
use Fabricate\Mechanism\Session\ElicitationFailed;
use Fabricate\Mechanism\Session\UrlElicitationRequired;
use Fabricate\Mechanism\Session\Actions\CancelledAction;
use Fabricate\Mechanism\Session\Actions\InitializedAction;
use Fabricate\NutsAndBolts\Messages\Elicitation\ElicitAnswer;
use Fabricate\NutsAndBolts\Messages\Elicitation\ElicitRequest;
use Fabricate\NutsAndBolts\Messages\Elicitation\ElicitationCompleteNotification;

final class UrlAskingServer extends MCPServer
{
    protected array $entity_info = ['name' => 'url-asking', 'version' => '1'];

    protected array $entity_capabilities = ['tools' => [], 'prompts' => []];

    public function __construct()
    {
        $this->tools = [
            new ToolDefinition('connect', action: function (): TextProperty {
                try {
                    $answer = app(Elicitation::class)->askUrl('Sign in to continue.', 'https://example.test/connect?e=e-1', 'e-1');
                } catch (ElicitationFailed $e) {
                    return new TextProperty("failed: {$e->getMessage()}");
                }

                $this->notify(new ElicitationCompleteNotification('e-1'));

                return new TextProperty($answer->action->value.' '.json_encode($answer->content));
            }),
            new ToolDefinition('locked', action: fn () => throw new UrlElicitationRequired([
                ['message' => 'Pay first.', 'url' => 'https://example.test/pay?e=e-3', 'elicitationId' => 'e-3'],
            ])),
            new ToolDefinition('form', action: fn (): TextProperty => new TextProperty(
                app(Elicitation::class)->ask('Name?', new ObjectSchema(['name' => new StringProperty()]))->action->value,
            )),
        ];

        $this->prompts = [
            new PromptDefinition('locked', action: fn () => throw new UrlElicitationRequired([
                ['message' => 'Authorize the calendar.', 'url' => 'https://example.test/auth?e=e-2', 'elicitationId' => 'e-2'],
            ])),
        ];
    }
}

/** @return array{0: \Voyager\IOPools\EventLoop, 1: Closure, 2: Closure(): list<array>} */
function urlSession(array $client_capabilities, string $version = '2025-11-25'): array
{
    $loop = testLoop();
    bareApp([], $loop);
    $bag = new SessionBag();
    $bag->server_configuration = new UrlAskingServer();
    $bag->put('notifications', ['notifications/initialized' => InitializedAction::class, 'notifications/cancelled' => CancelledAction::class]);
    $session = new Session($bag, $loop);

    $out = [];
    $send = function (string $line) use (&$out): void { $out[] = json_decode($line, true); };
    $take = function () use (&$out): array { [$all, $out] = [$out, []]; return $all; };
    $feed = function (array $message) use ($session, $send): void {
        $session->handle(LineDecoder::decodeString(json_encode(['jsonrpc' => '2.0', ...$message])), $send);
    };

    $feed(['id' => 0, 'method' => 'initialize', 'params' => ['protocolVersion' => $version, 'capabilities' => (object) $client_capabilities, 'clientInfo' => ['name' => 'c', 'version' => '1']]]);
    $feed(['method' => 'notifications/initialized']);
    $take();

    return [$loop, $feed, $take];
}

/** @return list<array> every message up to and including the first that $match accepts */
function urlWaitFor(\Voyager\IOPools\EventLoop $loop, Closure $take, Closure $match): array
{
    $seen = [];
    $loop->until(function () use ($take, $match, &$seen): bool {
        array_push($seen, ...$take());

        return array_filter($seen, $match) !== [];
    });

    return $seen;
}

it('reads the elicitation modes a client declared, by version', function (array $capabilities, ?string $version, array $modes) {
    expect(Capabilities::elicitationModes($capabilities, $version))->toBe($modes);
})->with([
    'no capability' => [[], '2025-11-25', []],
    'empty object is form' => [['elicitation' => []], '2025-11-25', ['form']],
    'url alone' => [['elicitation' => ['url' => []]], '2025-11-25', ['url']],
    'both' => [['elicitation' => ['url' => [], 'form' => []]], '2025-11-25', ['form', 'url']],
    'url before 2025-11-25 is form' => [['elicitation' => ['url' => []]], '2025-06-18', ['form']],
]);

it('puts a URL elicitation on the wire with mode, id and url, and reads one back', function () {
    $request = ElicitRequest::url(4, 'Sign in.', 'https://example.test/x', 'e-9');

    expect(json_decode($request->toJson(), true)['params'])->toBe(['mode' => 'url', 'message' => 'Sign in.', 'elicitationId' => 'e-9', 'url' => 'https://example.test/x'])
        // a form request leaves mode out, as every version before 2025-11-25 sent it
        ->and(json_decode((new ElicitRequest(5, 'Name?', new ObjectSchema(['n' => new StringProperty()])))->toJson(), true)['params'])->not->toHaveKey('mode');

    $read = LineDecoder::decodeString($request->toJson());
    expect($read)->toBeInstanceOf(ElicitRequest::class)->and($read->mode)->toBe('url')->and($read->elicitation_id)->toBe('e-9');

    foreach ([
        ['mode' => 'url', 'message' => 'm', 'elicitationId' => 'e', 'url' => 'not a url'],
        ['mode' => 'url', 'message' => 'm', 'url' => 'https://example.test'],
        ['mode' => 'sideways', 'message' => 'm'],
    ] as $params) {
        expect(LineDecoder::decodeString(json_encode(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'elicitation/create', 'params' => $params]))->code)->toBe(-32602);
    }
});

it('sends the user to a URL mid-call, drops content from an accept, and says when it is complete', function () {
    [$loop, $feed, $take] = urlSession(['elicitation' => ['url' => []]]);
    $feed(['id' => 1, 'method' => 'tools/call', 'params' => ['name' => 'connect', 'arguments' => (object) []]]);

    $asked = urlWaitFor($loop, $take, fn (array $m): bool => ($m['method'] ?? null) === 'elicitation/create');
    $question = end($asked);
    expect($question['params'])->toBe(['mode' => 'url', 'message' => 'Sign in to continue.', 'elicitationId' => 'e-1', 'url' => 'https://example.test/connect?e=e-1']);

    $feed(['id' => $question['id'], 'result' => ['action' => 'accept', 'content' => ['leak' => 'x']]]);
    $done = urlWaitFor($loop, $take, fn (array $m): bool => ($m['id'] ?? null) === 1);
    $response = array_values(array_filter($done, fn (array $m): bool => ($m['id'] ?? null) === 1))[0];

    // the notification the tool queued follows its response, to this session only
    expect($response['result']['content'][0]['text'])->toBe('accept []')
        ->and(array_values(array_filter($done, fn (array $m): bool => ($m['method'] ?? null) === 'notifications/elicitation/complete')))
        ->toBe([['jsonrpc' => '2.0', 'method' => 'notifications/elicitation/complete', 'params' => ['elicitationId' => 'e-1']]]);
});

it('never sends URL mode to a client that declared form only, or before 2025-11-25, nor a form to a URL-only client', function () {
    foreach ([[['elicitation' => []], '2025-11-25'], [['elicitation' => ['url' => []]], '2025-06-18']] as [$caps, $version]) {
        [$loop, $feed, $take] = urlSession($caps, $version);
        $feed(['id' => 1, 'method' => 'tools/call', 'params' => ['name' => 'connect', 'arguments' => (object) []]]);
        $done = urlWaitFor($loop, $take, fn (array $m): bool => ($m['id'] ?? null) === 1);

        expect(lastOfUrl($done)['result']['content'][0]['text'])->toContain('URL elicitation is not available')
            ->and(array_filter($done, fn (array $m): bool => isset($m['method'])))->toBe([]);
    }

    [$loop, $feed, $take] = urlSession(['elicitation' => ['url' => []]]);
    $feed(['id' => 2, 'method' => 'tools/call', 'params' => ['name' => 'form', 'arguments' => (object) []]]);
    expect(lastOfUrl(urlWaitFor($loop, $take, fn (array $m): bool => ($m['id'] ?? null) === 2))['result']['content'][0]['text'])->toContain('did not declare form mode');
});

it('answers a request that needs a URL elicitation first with -32042, or an internal error to a client without URL mode', function () {
    [$loop, $feed, $take] = urlSession(['elicitation' => ['url' => []]]);
    $feed(['id' => 1, 'method' => 'prompts/get', 'params' => ['name' => 'locked']]);
    $error = lastOfUrl(urlWaitFor($loop, $take, fn (array $m): bool => ($m['id'] ?? null) === 1))['error'];

    expect($error['code'])->toBe(-32042)
        ->and($error['data']['elicitations'])->toBe([['mode' => 'url', 'message' => 'Authorize the calendar.', 'elicitationId' => 'e-2', 'url' => 'https://example.test/auth?e=e-2']]);

    // a tool's throw is not a tool failure: it is the same error, not an isError result
    $feed(['id' => 2, 'method' => 'tools/call', 'params' => ['name' => 'locked', 'arguments' => (object) []]]);
    expect(lastOfUrl(urlWaitFor($loop, $take, fn (array $m): bool => ($m['id'] ?? null) === 2))['error']['data']['elicitations'][0]['elicitationId'])->toBe('e-3');

    [$loop, $feed, $take] = urlSession(['elicitation' => []]);
    $feed(['id' => 1, 'method' => 'prompts/get', 'params' => ['name' => 'locked']]);
    expect(lastOfUrl(urlWaitFor($loop, $take, fn (array $m): bool => ($m['id'] ?? null) === 1))['error']['code'])->toBe(-32603);

    expect(fn () => new UrlElicitationRequired([]))->toThrow(InvalidArgumentException::class)
        ->and(fn () => new UrlElicitationRequired([['message' => 'm', 'url' => 'nope', 'elicitationId' => 'e']]))->toThrow(InvalidArgumentException::class);
});

it('declares the modes it has handlers for, answers each by mode, and hears completions', function () {
    $completed = [];
    [$client, $connection] = fakeClient();
    $client->onElicitUrl(fn (ElicitRequest $request): ElicitAnswer => ElicitAnswer::accept(['ignored' => true]))
        ->onElicitationComplete(function (string $id) use (&$completed): void { $completed[] = $id; });
    $client->open();

    // URL alone names its mode; adding form names both
    expect($connection->raw[0])->toContain('"elicitation":{"url":{}}');
    [$both, $both_wire] = fakeClient(name: 'both');
    $both->onElicit(fn () => ElicitAnswer::decline())->onElicitUrl(fn () => ElicitAnswer::decline())->open();
    expect($both_wire->raw[0])->toContain('"elicitation":{"form":{},"url":{}}');

    $connection->handshake(['tools' => []], '2025-11-25');
    $connection->reply(['id' => $connection->last()['id'], 'result' => ['tools' => []]]);
    $client->ready()->wait();

    $connection->reply(['id' => 'u1', 'method' => 'elicitation/create', 'params' => ['mode' => 'url', 'message' => 'Go.', 'elicitationId' => 'e-1', 'url' => 'https://example.test/go']]);
    expect($connection->last())->toBe(['jsonrpc' => '2.0', 'id' => 'u1', 'result' => ['action' => 'accept']]);

    // a form to a client that declared URL only breaks the negotiation
    $connection->reply(['id' => 'f1', 'method' => 'elicitation/create', 'params' => ['message' => 'Name?', 'requestedSchema' => ['type' => 'object', 'properties' => ['n' => ['type' => 'string']]]]]);
    expect($connection->last()['error']['code'])->toBe(-32602);

    $connection->reply(['method' => 'notifications/elicitation/complete', 'params' => ['elicitationId' => 'e-1']]);
    expect($completed)->toBe(['e-1']);
});

function lastOfUrl(array $messages): array
{
    return $messages[array_key_last($messages)];
}
