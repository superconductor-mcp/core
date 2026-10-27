<?php

use Fabricate\Server\MCPServer;
use Fabricate\NutsAndBolts\LineDecoder;
use Fabricate\NutsAndBolts\ToolDefinition;
use Fabricate\NutsAndBolts\Schema\TextProperty;
use Fabricate\NutsAndBolts\Schema\StringProperty;
use Fabricate\NutsAndBolts\Schema\ObjectSchema;
use Fabricate\NutsAndBolts\Schema\StructuredContent;
use Fabricate\Mechanism\Session\Session;
use Fabricate\Mechanism\Session\SessionBag;
use Fabricate\Mechanism\Session\Elicitation;
use Fabricate\Mechanism\Session\Actions\CancelledAction;
use Fabricate\Mechanism\Session\Actions\InitializedAction;

final class AskingServer extends MCPServer
{
    protected array $entity_info = ['name' => 'asking', 'version' => '1'];

    protected array $entity_capabilities = ['tools' => []];

    public function __construct()
    {
        $schema = new ObjectSchema(['name' => new StringProperty(min_length: 2)], ['name']);

        $this->tools = [
            new ToolDefinition('greet', action: function () use ($schema): TextProperty {
                $answer = app(Elicitation::class)->ask('Your name?', $schema);

                return new TextProperty($answer->accepted() ? "hello {$answer->content['name']}" : $answer->action->value);
            }),
            new ToolDefinition('measure', action: fn (): StructuredContent => new StructuredContent(['mm' => 12.5])),
        ];
    }
}

/** @return array{0: Session, 1: \Voyager\IOPools\EventLoop, 2: Closure, 3: Closure(): list<array>} */
function session(array $client_capabilities = ['elicitation' => []], string $version = '2025-06-18', array $config = []): array
{
    $loop = testLoop();
    bareApp($config, $loop);
    $bag = new SessionBag();
    $bag->server_configuration = new AskingServer();
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

    return [$session, $loop, $feed, $take];
}

function lastOf(array $messages): array
{
    return $messages[array_key_last($messages)];
}

function waitFor(\Voyager\IOPools\EventLoop $loop, Closure $take, Closure $match): array
{
    $seen = [];
    $loop->until(function () use ($take, $match, &$seen): bool {
        array_push($seen, ...$take());

        return array_filter($seen, $match) !== [];
    });

    return $seen;
}

it('asks the user mid-call through the client and finishes with the answer', function () {
    [$session, $loop, $feed, $take] = session();
    $feed(['id' => 1, 'method' => 'tools/call', 'params' => ['name' => 'greet', 'arguments' => (object) []]]);

    $asked = waitFor($loop, $take, fn (array $m): bool => ($m['method'] ?? null) === 'elicitation/create');
    $question = array_values(array_filter($asked, fn (array $m): bool => ($m['method'] ?? null) === 'elicitation/create'))[0];
    expect($question['params']['message'])->toBe('Your name?')->and($question['params']['requestedSchema']['required'])->toBe(['name']);

    $feed(['id' => $question['id'], 'result' => ['action' => 'accept', 'content' => ['name' => 'Ada']]]);
    $done = waitFor($loop, $take, fn (array $m): bool => ($m['id'] ?? null) === 1);

    expect(end($done)['result']['content'][0]['text'])->toBe('hello Ada');
});

it('passes a decline through and refuses an answer that breaks the schema', function () {
    [$session, $loop, $feed, $take] = session();

    $feed(['id' => 1, 'method' => 'tools/call', 'params' => ['name' => 'greet', 'arguments' => (object) []]]);
    $q = waitFor($loop, $take, fn (array $m): bool => ($m['method'] ?? null) === 'elicitation/create');
    $feed(['id' => end($q)['id'], 'result' => ['action' => 'decline']]);
    expect(lastOf(waitFor($loop, $take, fn (array $m): bool => ($m['id'] ?? null) === 1))['result']['content'][0]['text'])->toBe('decline');

    $feed(['id' => 2, 'method' => 'tools/call', 'params' => ['name' => 'greet', 'arguments' => (object) []]]);
    $q = waitFor($loop, $take, fn (array $m): bool => ($m['method'] ?? null) === 'elicitation/create');
    $feed(['id' => end($q)['id'], 'result' => ['action' => 'accept', 'content' => ['name' => 'A']]]);
    $result = lastOf(waitFor($loop, $take, fn (array $m): bool => ($m['id'] ?? null) === 2))['result'];
    expect($result['isError'])->toBeTrue()->and($result['content'][0]['text'])->toContain('at least 2');
});

it('does not ask a client that did not declare elicitation, or one on an older version', function () {
    foreach ([[[], '2025-06-18'], [['elicitation' => []], '2025-03-26']] as [$caps, $version]) {
        [$session, $loop, $feed, $take] = session($caps, $version);
        $feed(['id' => 1, 'method' => 'tools/call', 'params' => ['name' => 'greet', 'arguments' => (object) []]]);
        $result = lastOf(waitFor($loop, $take, fn (array $m): bool => ($m['id'] ?? null) === 1))['result'];

        expect($result['isError'])->toBeTrue()->and($result['content'][0]['text'])->toContain('not available');
    }
});

it('gives up on an unanswered question at the timeout, and when the asking call is cancelled', function () {
    [$session, $loop, $feed, $take] = session(config: ['elicitation' => ['timeout' => 0.05]]);
    $feed(['id' => 1, 'method' => 'tools/call', 'params' => ['name' => 'greet', 'arguments' => (object) []]]);
    $seen = waitFor($loop, $take, fn (array $m): bool => ($m['id'] ?? null) === 1);

    expect(array_column(array_filter($seen, fn (array $m): bool => isset($m['method'])), 'method'))->toBe(['elicitation/create', 'notifications/cancelled'])
        ->and(end($seen)['result']['content'][0]['text'])->toContain('no answer within');

    [$session, $loop, $feed, $take] = session();
    $feed(['id' => 1, 'method' => 'tools/call', 'params' => ['name' => 'greet', 'arguments' => (object) []]]);
    waitFor($loop, $take, fn (array $m): bool => ($m['method'] ?? null) === 'elicitation/create');
    $feed(['method' => 'notifications/cancelled', 'params' => ['requestId' => 1]]);
    $seen = waitFor($loop, $take, fn (array $m): bool => ($m['method'] ?? null) === 'notifications/cancelled');

    // the cancelled call's own response is dropped, as cancellation requires
    expect(array_filter($seen, fn (array $m): bool => ($m['id'] ?? null) === 1))->toBe([]);
});

it('refuses a method whose capability the server did not declare, and returns structured content', function () {
    [$session, $loop, $feed, $take] = session();

    $feed(['id' => 1, 'method' => 'prompts/list']);
    expect($take()[0]['error']['code'])->toBe(-32601);

    $feed(['id' => 2, 'method' => 'tools/call', 'params' => ['name' => 'measure', 'arguments' => (object) []]]);
    $result = lastOf(waitFor($loop, $take, fn (array $m): bool => ($m['id'] ?? null) === 2))['result'];
    expect($result['structuredContent'])->toBe(['mm' => 12.5])->and($result['content'][0]['text'])->toBe('{"mm":12.5}');
});

it('knows whether the session takes batches', function () {
    expect(session(version: '2025-03-26')[0]->bag->takesBatches())->toBeTrue()
        ->and(session()[0]->bag->takesBatches())->toBeFalse();
});
