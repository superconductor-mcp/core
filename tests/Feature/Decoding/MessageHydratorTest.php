<?php

use Fabricate\Contracts\Schema\Messaging\MCPError;
use Fabricate\Contracts\Schema\Messaging\Initialize\InitializeRequest;
use Fabricate\Contracts\Schema\Messaging\Initialize\InitializedNotification;
use Fabricate\Mechanism\MessageDecoder;
use Fabricate\Mechanism\MessageHydrator;
use Fabricate\Schema\V20241105;
use Fabricate\Schema\V20250326;
use Fabricate\Schema\V20250618;
use Fabricate\Schema\V20251125;
use Fabricate\Schema\V20260728;
use Voyager\Config\Repository;
use Voyager\Vessel\ControlPanel;

beforeEach(function () {
    ControlPanel::setInstance($this->panel = new ControlPanel());
    $this->panel->registerInstance('config', new Repository(['mcp' => require __DIR__.'/../../../config/mcp.php']));
});

afterEach(fn() => ControlPanel::setInstance(null));

function hydrate(array $wire, ?string $request_method = null): mixed
{
    return MessageHydrator::hydrate(MessageDecoder::fromWire(json_encode($wire)), $request_method);
}

const V5_META = [
    'io.modelcontextprotocol/protocolVersion' => '2026-07-28',
    'io.modelcontextprotocol/clientCapabilities' => ['elicitation' => ['form' => []]],
];

it('builds a 2026-07-28 request when _meta carries the protocol version and capabilities.', function () {
    $request = hydrate(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/call', 'params' => ['name' => 'echo', 'arguments' => ['text' => 'hi'], '_meta' => V5_META + ['progressToken' => 'p']]]);

    expect($request)->toBeInstanceOf(V20260728\Messaging\Tools\CallToolRequest::class)
        ->and($request->params->_meta->toArray()['progressToken'])->toBe('p')
        ->and($request->toArray()['params']['arguments'])->toBe(['text' => 'hi']);
});

it('falls back to 2025-11-25 for the same request without the 2026-07-28 _meta.', function () {
    $request = hydrate(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/call', 'params' => ['name' => 'echo', 'task' => ['ttl' => 5]]]);

    expect($request)->toBeInstanceOf(V20251125\Messaging\Tools\CallToolRequest::class)
        ->and($request->params->task->toArray())->toBe(['ttl' => 5]);
});

it('builds the concrete initialize request of the version it names.', function (string $version, string $class) {
    $request = hydrate(['jsonrpc' => '2.0', 'id' => 7, 'method' => 'initialize', 'params' => [
        'protocolVersion' => $version,
        'capabilities' => ['roots' => ['listChanged' => true]],
        'clientInfo' => ['name' => 'probe', 'version' => '1.0'],
    ]]);

    expect($request)->toBeInstanceOf($class)->toBeInstanceOf(InitializeRequest::class)
        ->and($request->protocolVersion())->toBe($version);
})->with([
    ['2024-11-05', V20241105\Messaging\Initialize\InitializeRequest::class],
    ['2025-03-26', V20250326\Messaging\Initialize\InitializeRequest::class],
    ['2025-06-18', V20250618\Messaging\Initialize\InitializeRequest::class],
    ['2025-11-25', V20251125\Messaging\Initialize\InitializeRequest::class],
]);

it('builds the concrete notification of a flat-era version.', function () {
    $notification = hydrate(['jsonrpc' => '2.0', 'method' => 'notifications/initialized']);

    expect($notification)->toBeInstanceOf(InitializedNotification::class);
});

it('builds a notification.', function () {
    $notification = hydrate(['jsonrpc' => '2.0', 'method' => 'notifications/cancelled', 'params' => ['requestId' => 3, 'reason' => 'late']]);

    expect($notification)->toBeInstanceOf(V20260728\Messaging\Cancellation\CancelledNotification::class)
        ->and($notification->toArray()['params'])->toBe(['requestId' => 3, 'reason' => 'late']);
});

it('builds a result from the method of the request it answers.', function () {
    $result = hydrate(['jsonrpc' => '2.0', 'id' => 1, 'result' => ['tools' => [['name' => 't', 'inputSchema' => ['type' => 'object']]]]], 'tools/list');

    expect($result)->toBeInstanceOf(V20251125\Messaging\Tools\ListToolsResult::class)
        ->and($result->toArray()['tools'][0]['name'])->toBe('t');
});

it('builds a task result when a tools/call answer carries a task.', function () {
    $result = hydrate(['jsonrpc' => '2.0', 'id' => 1, 'result' => ['task' => ['taskId' => 't1', 'status' => 'working', 'createdAt' => 'now', 'lastUpdatedAt' => 'now', 'ttl' => null]]], 'tools/call');

    expect($result)->toBeInstanceOf(V20251125\Messaging\Tasks\CreateTaskResult::class);
});

it('builds an input-required result with its keyed input requests.', function () {
    $result = hydrate(['jsonrpc' => '2.0', 'id' => 1, 'result' => [
        'resultType' => 'input_required',
        'inputRequests' => ['r1' => ['method' => 'roots/list'], 'r2' => ['method' => 'elicitation/create', 'params' => ['mode' => 'url', 'message' => 'm', 'url' => 'https://x']]],
        'requestState' => 's',
    ]], 'tools/call');

    expect($result)->toBeInstanceOf(V20260728\Messaging\Inputs\InputRequiredResult::class)
        ->and(array_keys($result->inputRequests->requests))->toBe(['r1', 'r2'])
        ->and($result->toArray()['inputRequests']['r2']['params']['url'])->toBe('https://x');
});

it('builds an error response.', function () {
    $error = hydrate(['jsonrpc' => '2.0', 'id' => 1, 'error' => ['code' => -32601, 'message' => 'nope']]);

    expect($error)->toBeInstanceOf(V20260728\Messaging\MCPError::class)->and($error->code)->toBe(-32601);
});

it('names no protocol version when no version knows the method.', function () {
    $error = hydrate(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'funck']);

    expect($error->message)->toBe('Method not found: [funck].');
});

it('answers a message no version accepts with an MCPError that names the problem.', function (array $wire, ?string $method, int $code, string $needle) {
    $error = hydrate($wire, $method);

    expect($error)->toBeInstanceOf(MCPError::class)
        ->and($error->code)->toBe($code)
        ->and($error->message)->toContain($needle);
})->with([
    'unknown method' => [['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/fly'], null, -32601, 'tools/fly'],
    'missing member' => [['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/call', 'params' => []], null, -32602, 'params.name'],
    'wrong type' => [['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/call', 'params' => ['name' => 5]], null, -32602, 'params.name must be a string'],
    'bad nested member' => [['jsonrpc' => '2.0', 'id' => 1, 'method' => 'initialize', 'params' => ['protocolVersion' => '2025-06-18', 'capabilities' => [], 'clientInfo' => ['name' => 'x']]], null, -32602, 'params.clientInfo.version'],
    'initialize naming an unknown version' => [['jsonrpc' => '2.0', 'id' => 1, 'method' => 'initialize', 'params' => ['protocolVersion' => '1999-01-01', 'capabilities' => [], 'clientInfo' => ['name' => 'x', 'version' => '1']]], null, -32602, '1999-01-01'],
    'bad enum' => [['jsonrpc' => '2.0', 'method' => 'notifications/message', 'params' => ['level' => 'loud', 'data' => 1]], null, -32602, 'params.level'],
    'response without a pending request' => [['jsonrpc' => '2.0', 'id' => 9, 'result' => []], null, -32600, '[9]'],
    'result for an unknown method' => [['jsonrpc' => '2.0', 'id' => 1, 'result' => []], 'tools/fly', -32601, 'tools/fly'],
    'unknown error code' => [['jsonrpc' => '2.0', 'id' => 1, 'error' => ['code' => -1, 'message' => 'x']], null, -32600, '-1'],
    'invalid envelope' => [['jsonrpc' => '1.0', 'id' => 1, 'method' => 'ping'], null, -32600, 'jsonrpc'],
]);

it('realigns a request to the negotiated protocol version.', function (string $version, string $class) {
    $decoded = MessageDecoder::fromWire('{"jsonrpc":"2.0","id":1,"method":"ping"}');

    expect(MessageHydrator::hydrate($decoded))->toBeInstanceOf(V20251125\Messaging\Ping\PingRequest::class)
        ->and(MessageHydrator::hydrateAs($decoded, $version))->toBeInstanceOf($class)
        ->and(MessageHydrator::hydrateAs($decoded, $version))->toBeInstanceOf(Fabricate\Contracts\Schema\Messaging\Ping\PingRequest::class);
})->with([
    ['2024-11-05', V20241105\Messaging\Ping\PingRequest::class],
    ['2025-03-26', V20250326\Messaging\Ping\PingRequest::class],
    ['2025-06-18', V20250618\Messaging\Ping\PingRequest::class],
    ['2025-11-25', V20251125\Messaging\Ping\PingRequest::class],
]);

it('refuses a message the negotiated version does not have.', function () {
    $decoded = MessageDecoder::fromWire('{"jsonrpc":"2.0","id":1,"method":"tasks/list"}');

    expect(MessageHydrator::hydrateAs($decoded, '2024-11-05'))->toBeInstanceOf(MCPError::class)
        ->and(MessageHydrator::hydrateAs($decoded, '2025-11-25'))->toBeInstanceOf(V20251125\Messaging\Tasks\ListTasksRequest::class);
});

it('refuses a protocol version it does not know.', function () {
    $error = MessageHydrator::hydrateAs(MessageDecoder::fromWire('{"jsonrpc":"2.0","id":1,"method":"ping"}'), '1999-01-01');

    expect($error)->toBeInstanceOf(MCPError::class)
        ->and($error->toArray()['code'])->toBe(-32602);
});
