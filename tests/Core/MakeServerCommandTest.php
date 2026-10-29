<?php

use Fabricate\Core\Providers\SuperconductorMCPServiceProvider;
use Fabricate\NutsAndBolts\Schema\TextProperty;
use Fabricate\Server\DiscoverServers;
use Fabricate\Server\MCPServer;
use Fabricate\Server\ServerRepository;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Voyager\Console\ComputerConsoleInstance;
use Voyager\Contracts\Console\Kernel;
use Voyager\Core\VenusianVoyager;
use Voyager\Filesystem\Filesystem;
use Voyager\Vessel\ControlPanel;

// Each test generates into an application of its own: a temporary base path whose composer.json maps App\ to app/.
beforeEach(function () {
    $this->root = sys_get_temp_dir().'/mcp-make-server-'.bin2hex(random_bytes(6));
    mkdir($this->root.'/app', 0777, true);
    mkdir($this->root.'/bootstrap/cache', 0777, true);
    mkdir($this->root.'/config');
    file_put_contents($this->root.'/.env', '');
    file_put_contents($this->root.'/composer.json', json_encode(['autoload' => ['psr-4' => ['App\\' => 'app/']]]));

    $this->autoload = function (string $class) {
        if (str_starts_with($class, 'App\\')) {
            $file = $this->root.'/app/'.str_replace('\\', '/', substr($class, 4)).'.php';

            if (is_file($file)) {
                require $file;
            }
        }
    };
    spl_autoload_register($this->autoload);

    $this->app = VenusianVoyager::setup(base_path: $this->root)->create();
    $this->kernel = $this->app->make(Kernel::class);
    $this->kernel->bootstrap();
    $this->app->register(SuperconductorMCPServiceProvider::class);

    $this->make = function (string ...$arguments): array {
        $output = new BufferedOutput();
        $status = $this->kernel->handle(new ArgvInput(['computer', 'make:mcp-server', ...$arguments]), $output);

        return [$status, $output->fetch()];
    };
});

afterEach(function () {
    spl_autoload_unregister($this->autoload);
    // the application's bootstrap installs its error and exception handlers
    restore_error_handler();
    restore_exception_handler();
    ComputerConsoleInstance::forgetBootstrappers();
    (new Filesystem)->deleteDirectory($this->root);
    ControlPanel::setInstance(null);
});

it('writes a server into the discovery namespace, named in kebab case, and discovery finds it as it is', function () {
    [$status, $output] = ($this->make)('WeatherStation');

    $file = $this->root.'/app/Mcp/Servers/WeatherStation.php';
    $class = 'App\\Mcp\\Servers\\WeatherStation';

    expect($status)->toBe(0)
        ->and($output)->toContain('created successfully')
        ->and(file_get_contents($file))
            ->toContain('namespace App\\Mcp\\Servers;')
            ->toContain("#[Server('weather-station')]")
            ->toContain('class WeatherStation extends MCPServer')
            ->not->toContain('{{')
        ->and(DiscoverServers::within($this->root.'/app/Mcp/Servers', 'App\\Mcp\\Servers'))->toBe([$class])
        ->and(is_subclass_of($class, MCPServer::class))->toBeTrue()
        ->and(ServerRepository::nameFor($class))->toBe('weather-station');
});

it('generates a server whose example tool runs', function () {
    ($this->make)('Echoing');

    $server = new ('App\\Mcp\\Servers\\Echoing');
    [$tool] = $server->tools();

    expect($server->serverInfo())->toBe(['name' => 'echoing', 'version' => '1.0.0'])
        ->and($server->capabilities())->toBe(['tools' => ['listChanged' => false]])
        ->and($tool->name)->toBe('echo')
        ->and($tool->required)->toBe(['input'])
        ->and(($tool->action)('hello'))->toEqual(new TextProperty('hello'));
});

it('registers the generated server with the repository', function () {
    ($this->make)('Registered');

    $repository = $this->app->make(ServerRepository::class);
    $repository->register('App\\Mcp\\Servers\\Registered');

    expect($repository->has('registered'))->toBeTrue()
        ->and($repository->get('registered'))->toBeInstanceOf(MCPServer::class);
});

it('writes where mcp.servers.default_discovery_namespace points', function () {
    config(['mcp.servers.default_discovery_namespace' => 'App\\Agents']);

    [$status] = ($this->make)('Scout');

    expect($status)->toBe(0)
        ->and(is_file($this->root.'/app/Agents/Scout.php'))->toBeTrue()
        ->and(file_get_contents($this->root.'/app/Agents/Scout.php'))->toContain('namespace App\\Agents;');
});

it('does not overwrite a server that exists, unless forced', function () {
    ($this->make)('Kept');
    $file = $this->root.'/app/Mcp/Servers/Kept.php';
    file_put_contents($file, '<?php // mine');

    [, $output] = ($this->make)('Kept');

    expect($output)->toContain('already exists')
        ->and(file_get_contents($file))->toBe('<?php // mine');

    [$forced] = ($this->make)('Kept', '--force');

    expect($forced)->toBe(0)
        ->and(file_get_contents($file))->toContain('class Kept extends MCPServer');
});
