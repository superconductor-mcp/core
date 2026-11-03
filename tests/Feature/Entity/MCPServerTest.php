<?php

use Fabricate\Core\Routing\ProcedureRouter;
use Fabricate\Mechanism\MessageDecoder;
use Fabricate\Server\MCPServer;
use Fabricate\Server\ServerCapabilitiesFactory;
use Fabricate\Server\ServerConfiguration;
use Voyager\Config\Repository;
use Voyager\Vessel\ControlPanel;

beforeEach(function () {
    ControlPanel::setInstance($panel = new ControlPanel());
    $panel->registerInstance('config', new Repository(['mcp' => require __DIR__.'/../../../config/mcp.php']));
    $panel->registerSingleton(ProcedureRouter::class);

    require __DIR__.'/../../../routes/mcp.php';
});

afterEach(fn() => ControlPanel::setInstance(null));

function loggingServer(): ServerConfiguration
{
    return new class(new ServerCapabilitiesFactory()) extends ServerConfiguration {
        protected array $supported_protocol_versions = ['2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25', '2026-07-28'];
        protected bool $logging = true;
    };
}

function receive(MCPServer $server, string $wire): ?string
{
    return $server->receiveMessage(MessageDecoder::fromWire($wire))?->toJson();
}

it('answers a request in its protocol version with the request id.', function (string $version) {
    expect(receive(new MCPServer(loggingServer(), $version), '{"jsonrpc":"2.0","id":7,"method":"ping"}'))
        ->toBe('{"jsonrpc":"2.0","id":7,"result":{}}');
})->with(['2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25']);

it('dispatches to procedures with its own configuration.', function () {
    $configuration = loggingServer();

    expect(receive(new MCPServer($configuration, '2025-06-18'), '{"jsonrpc":"2.0","id":1,"method":"logging/setLevel","params":{"level":"error"}}'))
        ->toBe('{"jsonrpc":"2.0","id":1,"result":{}}')
        ->and($configuration->logLevel())->toBe(\Fabricate\Contracts\Schema\LoggingLevel::ERROR);
});

it('answers a request it cannot serve with an error response.', function (string $wire, int $code) {
    $reply = json_decode(receive(new MCPServer(loggingServer(), '2025-06-18'), $wire), true);

    expect($reply['id'])->toBe(3)
        ->and($reply['error']['code'])->toBe($code);
})->with([
    'unknown method' => ['{"jsonrpc":"2.0","id":3,"method":"funck"}', -32601],
    'tasks are not in this version' => ['{"jsonrpc":"2.0","id":3,"method":"tasks/list"}', -32601],
    'second initialize' => ['{"jsonrpc":"2.0","id":3,"method":"initialize","params":{"protocolVersion":"2025-06-18","capabilities":{},"clientInfo":{"name":"c","version":"1"}}}', -32600],
    'second initialize naming another version' => ['{"jsonrpc":"2.0","id":3,"method":"initialize","params":{"protocolVersion":"2024-11-05","capabilities":{},"clientInfo":{"name":"c","version":"1"}}}', -32600],
    'capability off' => ['{"jsonrpc":"2.0","id":3,"method":"tools/list"}', -32601],
]);

it('never answers a notification or a response.', function (string $wire) {
    expect(receive(new MCPServer(loggingServer(), '2025-06-18'), $wire))->toBeNull();
})->with([
    'known notification' => ['{"jsonrpc":"2.0","method":"notifications/cancelled","params":{"requestId":1}}'],
    'unknown notification' => ['{"jsonrpc":"2.0","method":"notifications/bogus"}'],
    'response to nothing' => ['{"jsonrpc":"2.0","id":99,"result":{}}'],
    'error response' => ['{"jsonrpc":"2.0","id":98,"error":{"code":-32601,"message":"x"}}'],
]);
