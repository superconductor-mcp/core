<?php

use Fabricate\Contracts\Client\ClientStatus;
use Fabricate\Client\RequestFailed;
use Fabricate\NutsAndBolts\Messages\Tools\CallToolResponse;
use Fabricate\NutsAndBolts\Messages\Elicitation\ElicitAnswer;
use Fabricate\NutsAndBolts\Messages\Elicitation\ElicitRequest;

it('opens with initialize, finishes the handshake, and caches every page of each advertised list', function () {
    [$client, $connection] = fakeClient();

    $client->open();
    expect($client->status())->toBe(ClientStatus::Connecting)
        ->and($connection->opened)->toBeTrue()
        ->and($connection->sent[0]['method'])->toBe('initialize')
        ->and($connection->sent[0]['id'])->toBe(0)
        ->and($connection->sent[0]['params']['protocolVersion'])->toBe('2025-11-25');

    $connection->handshake(['tools' => ['listChanged' => true], 'prompts' => []]);
    expect($client->status())->toBe(ClientStatus::Connected)
        ->and($connection->methods())->toBe(['initialize', 'notifications/initialized', 'tools/list', 'prompts/list'])
        ->and($client->serverInfo()['name'])->toBe('bench')
        ->and($client->ready()->settled())->toBeFalse();

    // tools come in two pages; the client asks for the second one by itself
    $connection->reply(['id' => $connection->sent[2]['id'], 'result' => ['tools' => [['name' => 'a', 'inputSchema' => ['type' => 'object']]], 'nextCursor' => 'c2']]);
    expect($connection->last()['method'])->toBe('tools/list')->and($connection->last()['params']['cursor'])->toBe('c2');

    $connection->reply(['id' => $connection->last()['id'], 'result' => ['tools' => [['name' => 'b', 'inputSchema' => ['type' => 'object']]]]]);
    $connection->reply(['id' => $connection->sent[3]['id'], 'result' => ['prompts' => [['name' => 'greet']]]]);

    expect($client->ready()->wait())->toBe($client)
        ->and(array_column($client->tools(), 'name'))->toBe(['a', 'b'])
        ->and(array_column($client->prompts(), 'name'))->toBe(['greet'])
        ->and($client->resources())->toBe([]);
});

it('is ready right after the handshake when the server advertises nothing to list', function () {
    [$client, $connection] = fakeClient();
    $client->open();
    $connection->handshake();

    expect($client->ready()->wait())->toBe($client)->and($connection->methods())->toBe(['initialize', 'notifications/initialized']);
});

it('fails and closes when the server answers with a version it cannot speak', function () {
    [$client, $connection] = fakeClient();
    $client->open();
    $connection->handshake(version: '2030-01-01');

    expect($client->status())->toBe(ClientStatus::Error)
        ->and($client->lastError())->toContain('2030-01-01')
        ->and($connection->closed)->toBeTrue();
    expect(fn () => $client->ready()->wait())->toThrow(RequestFailed::class);
});

it('fails when the server refuses initialize or the connection cannot start', function () {
    [$client, $connection] = fakeClient();
    $client->open();
    $connection->reply(['id' => 0, 'error' => ['code' => -32600, 'message' => 'go away']]);
    expect($client->status())->toBe(ClientStatus::Error)->and($client->lastError())->toContain('go away');

    [$client, $connection] = fakeClient();
    $connection->fail_open = true;
    $client->open();
    expect($client->status())->toBe(ClientStatus::Error)->and($client->lastError())->toContain('could not start');
});

it('gives up on the handshake at the connect deadline', function () {
    $loop = testLoop();
    [$client] = fakeClient(['client' => ['connect_timeout' => 0.05]], $loop);
    $client->open();

    $loop->until(fn (): bool => $client->status() !== ClientStatus::Connecting);

    expect($client->status())->toBe(ClientStatus::Error)->and($client->lastError())->toContain('handshake');
});

// a server that declares tools, past the handshake and its first tools/list
function connected(array $capabilities = ['tools' => ['listChanged' => true]], ?Closure $setup = null): array
{
    [$client, $connection] = fakeClient();
    $setup && $setup($client);
    $client->open();
    $connection->handshake($capabilities, '2025-06-18');

    if (isset($capabilities['tools'])) {
        $connection->reply(['id' => $connection->last()['id'], 'result' => ['tools' => []]]);
    }

    $client->ready()->wait();

    return [$client, $connection];
}

it('settles a request with the typed response, or rejects it with the server error', function () {
    [$client, $connection] = connected();

    $call = $client->callTool('echo', ['text' => 'hi']);
    expect($connection->last()['method'])->toBe('tools/call')->and($connection->last()['params']['arguments'])->toBe(['text' => 'hi']);

    $connection->reply(['id' => $connection->last()['id'], 'result' => ['content' => [['type' => 'text', 'text' => 'hi']]]]);
    expect($call->wait())->toBeInstanceOf(CallToolResponse::class);

    $refused = $client->callTool('nope');
    $connection->reply(['id' => $connection->last()['id'], 'error' => ['code' => -32602, 'message' => 'Unknown tool: nope', 'data' => ['tool' => 'nope']]]);

    try {
        $refused->wait();
        $this->fail('expected a rejection');
    } catch (RequestFailed $e) {
        expect($e->rpc_code)->toBe(-32602)->and($e->data)->toBe(['tool' => 'nope'])->and($e->getMessage())->toContain('tools/call');
    }
});

it('never reuses one of its own ids, and ignores a reply nobody is waiting for', function () {
    [$client, $connection] = connected();
    $client->ping();
    $client->ping();

    $ids = array_column($connection->sent, 'id');
    expect($ids)->toBe(array_unique($ids));

    $connection->reply(['id' => 999, 'result' => []]);
    expect(log_entries()[array_key_last(log_entries())][1])->toContain('nobody is waiting');
});

it('answers the server ping, refuses a repeated server id, and refuses other server requests', function () {
    [$client, $connection] = connected();

    $connection->reply(['id' => 0, 'method' => 'ping']);          // the server's id 0 is fine even though the client used 0 too
    expect($connection->last())->toBe(['jsonrpc' => '2.0', 'id' => 0, 'result' => []]);

    $connection->reply(['id' => 0, 'method' => 'ping']);
    expect($connection->last()['error']['code'])->toBe(-32600)->and($connection->last()['error']['data'])->toContain('already been used');

    $connection->reply(['id' => 1, 'method' => 'roots/list']);
    expect($connection->last()['error']['code'])->toBe(-32601);
});

it('refills a cache when the server says its list changed', function () {
    [$client, $connection] = connected();

    $connection->reply(['method' => 'notifications/tools/list_changed']);
    expect($connection->last()['method'])->toBe('tools/list');

    $connection->reply(['id' => $connection->last()['id'], 'result' => ['tools' => [['name' => 'new', 'inputSchema' => ['type' => 'object']]]]]);
    expect(array_column($client->tools(), 'name'))->toBe(['new']);
});

it('puts a server log line into the application log at its level', function () {
    [$client, $connection] = connected();
    $connection->reply(['method' => 'notifications/message', 'params' => ['level' => 'warning', 'logger' => 'db', 'data' => ['rows' => 3]]]);

    [$level, $message, $context] = log_entries()[array_key_last(log_entries())];
    expect($level)->toBe('warning')->and($message)->toContain('db')->and($context)->toBe(['rows' => 3]);
});

it('rejects a request sent before the handshake is done or after the client closed', function () {
    [$client] = fakeClient();
    expect(fn () => $client->callTool('x')->wait())->toThrow(RequestFailed::class, 'not-connected');

    [$client, $connection] = fakeClient();
    $client->open();
    expect(fn () => $client->listTools()->wait())->toThrow(RequestFailed::class, 'connecting');
    expect($connection->methods())->toBe(['initialize']);
});

it('rejects everything pending and ends the connection on close', function () {
    [$client, $connection] = connected();
    $pending = $client->listTools();

    $client->close();

    expect($client->status())->toBe(ClientStatus::NotConnected)
        ->and($connection->closed)->toBeTrue()
        ->and($client->closed())->toBeTrue();
    expect(fn () => $pending->wait())->toThrow(RequestFailed::class, 'closed');
});

it('turns into an error when the server goes away on its own', function () {
    [$client] = connected();
    $client->connectionClosed('the server exited (exit code 1).');

    expect($client->status())->toBe(ClientStatus::Error)->and($client->lastError())->toContain('exit code 1');
});

it('does not ask a server for what it did not declare', function () {
    [$client] = connected();
    expect(fn () => $client->listPrompts()->wait())->toThrow(RequestFailed::class, 'did not declare prompts');
});

it('declares elicitation when it has a handler, answers with it, and refuses an off-schema answer', function () {
    $answers = [ElicitAnswer::accept(['name' => 'Ada']), ElicitAnswer::accept(['name' => 7])];
    [$client, $connection] = connected(setup: fn ($client) => $client->onElicit(function (ElicitRequest $request) use (&$answers): ElicitAnswer {
        return array_shift($answers);
    }));
    // an empty capability is a JSON object on the wire; Go and TS servers refuse an array
    expect($connection->raw[0])->toContain('"capabilities":{"elicitation":{}}');

    $ask = ['method' => 'elicitation/create', 'params' => ['message' => 'Name?', 'requestedSchema' => ['type' => 'object', 'properties' => ['name' => ['type' => 'string']]]]];
    $connection->reply(['id' => 'e1', ...$ask]);
    expect($connection->last())->toBe(['jsonrpc' => '2.0', 'id' => 'e1', 'result' => ['action' => 'accept', 'content' => ['name' => 'Ada']]]);

    $connection->reply(['id' => 'e2', ...$ask]);
    expect($connection->last()['error']['code'])->toBe(-32603)->and($connection->last()['error']['data'])->toContain('must be a string');

    // without a handler the request is one this client does not have
    [, $plain] = connected();
    $plain->reply(['id' => 'e3', ...$ask]);
    expect($plain->last()['error']['code'])->toBe(-32601);
});
