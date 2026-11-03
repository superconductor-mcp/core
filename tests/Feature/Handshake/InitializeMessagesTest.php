<?php

use Fabricate\Client\ClientCapabilitiesFactory;
use Fabricate\Client\ClientConfiguration;
use Fabricate\Contracts\Mechanism\Attributes\ClientConfig;
use Fabricate\Contracts\Mechanism\Attributes\ServerConfig;
use Fabricate\Contracts\Mechanism\MCPMechanismException;
use Fabricate\Contracts\Schema\Messaging\Initialize\InitializeRequest;
use Fabricate\Contracts\Schema\Messaging\Initialize\InitializeResult;
use Fabricate\Contracts\Schema\RequestId;
use Fabricate\Schema\V20241105;
use Fabricate\Schema\V20250326;
use Fabricate\Schema\V20250618;
use Fabricate\Schema\V20251125;
use Fabricate\Server\ServerCapabilitiesFactory;
use Fabricate\Server\ServerConfiguration;
use Voyager\Config\Repository;
use Voyager\Vessel\ControlPanel;

function handshakeClient(array $versions): ClientConfiguration
{
    return new #[ClientConfig('handshake-client', version: '1.0.0')] class(new ClientCapabilitiesFactory(), $versions) extends ClientConfiguration {
        protected bool|array $roots = ['listChanged' => true];

        public function __construct(ClientCapabilitiesFactory $factory, array $versions)
        {
            parent::__construct($factory);
            $this->supported_protocol_versions = $versions;
        }
    };
}

function handshakeServer(array $versions): ServerConfiguration
{
    return new #[ServerConfig('handshake-server', version: '2.0.0')] class(new ServerCapabilitiesFactory(), $versions) extends ServerConfiguration {
        protected bool|array $tools = ['listChanged' => true];

        public function __construct(ServerCapabilitiesFactory $factory, array $versions)
        {
            parent::__construct($factory);
            $this->supported_protocol_versions = $versions;
        }
    };
}

function appSupports(array $versions): void
{
    ControlPanel::getInstance()->registerInstance('config', new Repository(['mcp' => ['supported_protocol_versions' => $versions]]));
}

beforeEach(function () {
    ControlPanel::setInstance(new ControlPanel());
    appSupports(['2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25', '2026-07-28']);
});

afterEach(fn() => ControlPanel::setInstance(null));

dataset('handshake versions', [
    ['2024-11-05', V20241105\Messaging\Initialize\InitializeRequest::class, V20241105\Messaging\Initialize\InitializeResult::class],
    ['2025-03-26', V20250326\Messaging\Initialize\InitializeRequest::class, V20250326\Messaging\Initialize\InitializeResult::class],
    ['2025-06-18', V20250618\Messaging\Initialize\InitializeRequest::class, V20250618\Messaging\Initialize\InitializeResult::class],
    ['2025-11-25', V20251125\Messaging\Initialize\InitializeRequest::class, V20251125\Messaging\Initialize\InitializeResult::class],
]);

it('builds the concrete initialize request of a requested version it supports.', function (string $version, string $request_class) {
    $request = handshakeClient(['2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25', '2026-07-28'])->initializeRequest(new RequestId(1), $version);

    expect($request)->toBeInstanceOf($request_class)
        ->toBeInstanceOf(InitializeRequest::class)
        ->and($request->protocolVersion())->toBe($version);
})->with('handshake versions');

it('builds the concrete initialize result of a requested version it supports.', function (string $version, string $request_class, string $result_class) {
    $result = handshakeServer(['2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25', '2026-07-28'])->initializeResult($version);

    expect($result)->toBeInstanceOf($result_class)
        ->toBeInstanceOf(InitializeResult::class)
        ->and($result->protocolVersion)->toBe($version);
})->with('handshake versions');

it('requests the newest handshake version when none is passed in.', function () {
    $request = handshakeClient(['2024-11-05', '2025-06-18', '2026-07-28'])->initializeRequest(new RequestId(1));

    expect($request)->toBeInstanceOf(V20250618\Messaging\Initialize\InitializeRequest::class)
        ->and($request->protocolVersion())->toBe('2025-06-18');
});

it('falls back to the newest handshake version the entity supports.', function (string $requested) {
    $request = handshakeClient(['2024-11-05', '2025-03-26'])->initializeRequest(new RequestId(1), $requested);
    $result = handshakeServer(['2024-11-05', '2025-03-26'])->initializeResult($requested);

    expect($request->protocolVersion())->toBe('2025-03-26')
        ->and($result->protocolVersion)->toBe('2025-03-26');
})->with(['2025-11-25', '2026-07-28', '1999-01-01']);

it('falls back when the app config does not support the requested version.', function () {
    appSupports(['2024-11-05', '2025-03-26']);

    $request = handshakeClient(['2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25'])->initializeRequest(new RequestId(1), '2025-06-18');
    $result = handshakeServer(['2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25'])->initializeResult('2025-06-18');

    expect($request)->toBeInstanceOf(V20250326\Messaging\Initialize\InitializeRequest::class)
        ->and($result)->toBeInstanceOf(V20250326\Messaging\Initialize\InitializeResult::class);
});

it('announces the entity and its capabilities in the version it settles on.', function () {
    $request = handshakeClient(['2025-11-25'])->initializeRequest(new RequestId('abc'));
    $result = handshakeServer(['2024-11-05'])->initializeResult('2025-11-25');

    expect($request->toArray())->toMatchArray([
        'jsonrpc' => '2.0',
        'id' => 'abc',
        'method' => 'initialize',
    ])
        ->and(json_encode($request->toArray()['params']))->toBe('{"protocolVersion":"2025-11-25","capabilities":{"roots":{"listChanged":true}},"clientInfo":{"name":"handshake-client","version":"1.0.0"}}')
        ->and(json_encode($result->toArray()))->toBe('{"protocolVersion":"2024-11-05","capabilities":{"tools":{"listChanged":true}},"serverInfo":{"name":"handshake-server","version":"2.0.0"}}');
});

it('refuses to build a handshake for an entity with no handshake version.', function () {
    expect(fn () => handshakeClient(['2026-07-28'])->initializeRequest(new RequestId(1)))->toThrow(MCPMechanismException::class)
        ->and(fn () => handshakeServer(['2026-07-28'])->initializeResult('2025-06-18'))->toThrow(MCPMechanismException::class);
});
