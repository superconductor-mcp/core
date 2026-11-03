<?php

use Fabricate\Client\ClientCapabilitiesFactory;
use Fabricate\Client\ClientConfiguration;
use Fabricate\Contracts\Core\SuperconductorMCPException;
use Fabricate\Core\Providers\SuperconductorMCPServiceProvider;
use Fabricate\Server\ServerCapabilitiesFactory;
use Fabricate\Server\ServerConfiguration;
use Voyager\Config\Repository;
use Voyager\Core\RenderedInstance;
use Voyager\Vessel\ControlPanel;

function allVersions(): array
{
    return ['2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25', '2026-07-28'];
}

function everythingServer(): ServerConfiguration
{
    return new class(new ServerCapabilitiesFactory()) extends ServerConfiguration {
        protected array $supported_protocol_versions = ['2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25', '2026-07-28'];
        protected bool|array $tools = ['listChanged' => true];
        protected bool|array $prompts = ['listChanged' => true];
        protected bool|array $resources = ['listChanged' => true, 'subscribe' => true];
        protected bool $completions = true;
        protected bool $logging = true;
        protected bool|array $tasks = ['list' => true, 'cancel' => true, 'requests' => ['tools' => ['call' => true]]];
        protected bool|array $extensions = ['io.x/y' => []];
        protected bool|array $experimental = ['z' => []];
    };
}

function everythingClient(): ClientConfiguration
{
    return new class(new ClientCapabilitiesFactory()) extends ClientConfiguration {
        protected array $supported_protocol_versions = ['2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25', '2026-07-28'];
        protected bool|array $roots = ['listChanged' => true];
        protected bool|array $sampling = ['context' => true, 'tools' => true];
        protected bool|array $elicitation = ['form' => true, 'url' => true];
        protected bool|array $tasks = [
            'list' => true,
            'cancel' => true,
            'requests' => ['sampling' => ['createMessage' => true], 'elicitation' => ['create' => true]],
        ];
        protected bool|array $extensions = ['io.x/y' => []];
        protected bool|array $experimental = ['z' => []];
    };
}

beforeEach(function () {
    $config = new Repository(['mcp' => require __DIR__.'/../../../config/mcp.php']);

    ControlPanel::setInstance($panel = new ControlPanel());
    $panel->registerInstance('config', $config);
});

afterEach(fn() => ControlPanel::setInstance(null));

it('gives each version only the server capabilities it has.', function (string $version, string $expected) {
    expect(json_encode(everythingServer()->capabilities($version)->toArray()))->toBe($expected);
})->with([
    ['2024-11-05', '{"tools":{"listChanged":true},"prompts":{"listChanged":true},"resources":{"subscribe":true,"listChanged":true},"logging":{},"experimental":{"z":{}}}'],
    ['2025-03-26', '{"tools":{"listChanged":true},"prompts":{"listChanged":true},"resources":{"subscribe":true,"listChanged":true},"logging":{},"completions":{},"experimental":{"z":{}}}'],
    ['2025-06-18', '{"tools":{"listChanged":true},"prompts":{"listChanged":true},"resources":{"subscribe":true,"listChanged":true},"logging":{},"completions":{},"experimental":{"z":{}}}'],
    ['2025-11-25', '{"tools":{"listChanged":true},"prompts":{"listChanged":true},"resources":{"subscribe":true,"listChanged":true},"logging":{},"completions":{},"tasks":{"list":{},"cancel":{},"requests":{"tools":{"call":{}}}},"experimental":{"z":{}}}'],
    ['2026-07-28', '{"tools":{"listChanged":true},"prompts":{"listChanged":true},"resources":{"subscribe":true,"listChanged":true},"completions":{},"logging":{},"extensions":{"io.modelcontextprotocol\/tasks":{},"io.x\/y":{}},"experimental":{"z":{}}}'],
]);

it('gives each version only the client capabilities it has.', function (string $version, string $expected) {
    expect(json_encode(everythingClient()->capabilities($version)->toArray()))->toBe($expected);
})->with([
    ['2024-11-05', '{"sampling":{},"roots":{"listChanged":true},"experimental":{"z":{}}}'],
    ['2025-03-26', '{"sampling":{},"roots":{"listChanged":true},"experimental":{"z":{}}}'],
    ['2025-06-18', '{"sampling":{},"roots":{"listChanged":true},"elicitation":{},"experimental":{"z":{}}}'],
    ['2025-11-25', '{"sampling":{"context":{},"tools":{}},"roots":{"listChanged":true},"elicitation":{"form":{},"url":{}},"tasks":{"list":{},"cancel":{},"requests":{"sampling":{"createMessage":{}},"elicitation":{"create":{}}}},"experimental":{"z":{}}}'],
    ['2026-07-28', '{"elicitation":{"form":{},"url":{}},"roots":{},"sampling":{"context":{},"tools":{}},"extensions":{"io.x\/y":{}},"experimental":{"z":{}}}'],
]);

it('declares nothing when nothing is configured.', function (string $version) {
    $server = new class(new ServerCapabilitiesFactory()) extends ServerConfiguration {
        protected array $supported_protocol_versions = ['2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25', '2026-07-28'];
    };
    $client = new class(new ClientCapabilitiesFactory()) extends ClientConfiguration {
        protected array $supported_protocol_versions = ['2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25', '2026-07-28'];
    };

    expect($server->capabilities($version)->toArray())->toBe([])
        ->and($client->capabilities($version)->toArray())->toBe([]);
})->with(allVersions());

it('treats true as an empty capability and false as off at every depth.', function () {
    $server = new class(new ServerCapabilitiesFactory()) extends ServerConfiguration {
        protected array $supported_protocol_versions = ['2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25', '2026-07-28'];
        protected bool|array $tools = true;
        protected bool|array $prompts = false;
        protected bool|array $resources = ['subscribe' => false, 'listChanged' => true];
        protected bool|array $experimental = true;
    };

    expect(json_encode($server->capabilities('2025-06-18')->toArray()))
        ->toBe('{"tools":{},"resources":{"listChanged":true},"experimental":{}}');
});

it('switches on the parents of a nested capability.', function () {
    $server = new class(new ServerCapabilitiesFactory()) extends ServerConfiguration {
        protected array $supported_protocol_versions = ['2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25', '2026-07-28'];
        protected bool|array $tasks = ['requests' => ['tools' => ['call' => true]]];
    };

    expect(json_encode($server->capabilities('2025-11-25')->toArray()))
        ->toBe('{"tasks":{"requests":{"tools":{"call":{}}}}}');
});

it('answers the same whatever order the configuration is written in.', function () {
    $forward = new class(new ServerCapabilitiesFactory()) extends ServerConfiguration {
        protected array $supported_protocol_versions = ['2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25', '2026-07-28'];
        protected bool|array $resources = ['subscribe' => true, 'listChanged' => true];
        protected bool|array $tools = true;
    };
    $backward = new class(new ServerCapabilitiesFactory()) extends ServerConfiguration {
        protected array $supported_protocol_versions = ['2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25', '2026-07-28'];
        protected bool|array $tools = true;
        protected bool|array $resources = ['listChanged' => true, 'subscribe' => true];
    };

    expect(json_encode($forward->capabilities('2025-06-18')->toArray()))
        ->toBe(json_encode($backward->capabilities('2025-06-18')->toArray()))
        ->toBe(json_encode($forward->capabilities('2025-06-18')->toArray()));
});

it('lets a configuration compute a capability by overriding its config method.', function () {
    $server = new class(new ServerCapabilitiesFactory()) extends ServerConfiguration {
        protected array $supported_protocol_versions = ['2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25', '2026-07-28'];

        public function toolsConfig(): ?array
        {
            return ['listChanged' => true];
        }
    };

    expect(json_encode($server->capabilities('2026-07-28')->toArray()))->toBe('{"tools":{"listChanged":true}}');
});

it('reports what the built object supports.', function () {
    $capabilities = everythingServer()->capabilities('2024-11-05');

    expect($capabilities->supportsCapability('tools'))->toBeTrue()
        ->and($capabilities->supportsCapability('completions'))->toBeFalse();
});

it('rejects a capability key no version has rather than dropping it.', function () {
    $server = new class(new ServerCapabilitiesFactory()) extends ServerConfiguration {
        protected array $supported_protocol_versions = ['2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25', '2026-07-28'];
        protected bool|array $tools = ['listchanged' => true];
    };

    $server->capabilities('2026-07-28');
})->throws(SuperconductorMCPException::class, 'Unknown capability key tools.listchanged');

it('rejects a version the configuration does not support.', function () {
    $server = new class(new ServerCapabilitiesFactory()) extends ServerConfiguration {};

    $server->capabilities('2025-06-18');
})->throws(SuperconductorMCPException::class, 'Incompatible protocol version');

it('rejects a version the application config does not support.', function () {
    config()->set('mcp.supported_protocol_versions', ['2026-07-28']);

    everythingServer()->capabilities('2025-06-18');
})->throws(SuperconductorMCPException::class, 'Incompatible protocol version');

it('rejects a supported version that has no schema tree.', function () {
    config()->set('mcp.supported_protocol_versions', ['1999-01-01']);

    $server = new class(new ServerCapabilitiesFactory()) extends ServerConfiguration {
        protected array $supported_protocol_versions = ['1999-01-01'];
    };

    $server->capabilities('1999-01-01');
})->throws(SuperconductorMCPException::class, 'No ServerCapabilities for protocol version 1999-01-01');

it('merges the versions under an application mcp config that does not set them.', function () {
    $app = new RenderedInstance(sys_get_temp_dir());
    $app->registerInstance('config', $config = new Repository(['mcp' => ['supported_protocols' => ['2024-11-05']]]));

    (new SuperconductorMCPServiceProvider($app))->register();

    expect($config->get('mcp.supported_protocol_versions'))->toBe(allVersions())
        ->and($config->get('mcp.supported_protocols'))->toBe(['2024-11-05']);
});
