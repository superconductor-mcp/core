<?php

declare(strict_types=1);

namespace Fabricate\Server;

use InvalidArgumentException;
use Voyager\Contracts\Core\FrameworkCore;
use Voyager\NutsAndBolts\ServiceProvider;

class ServerServiceProvider extends ServiceProvider
{
    public const string DISCOVERY_KEY = 'mcp.servers.default_discovery_namespace';

    public function register(): void
    {
        $this->app->registerSingleton('mcp.servers', fn (FrameworkCore $app) => new ServerRepository($app));
        $this->app->alias('mcp.servers', ServerRepository::class);
    }

    public function boot(): void
    {
        $servers = $this->app->make(ServerRepository::class);
        $namespace = trim((string) $this->app['config']->get(self::DISCOVERY_KEY, 'App\\Mcp\\Servers'), '\\');

        foreach (DiscoverServers::within($this->discoveryPath($namespace), $namespace) as $class) {
            $servers->register($class);
        }
    }

    // Servers live in the application's own PSR-4 tree, so the folder follows from the namespace.
    protected function discoveryPath(string $namespace): string
    {
        $app_namespace = trim($this->app->getNamespace(), '\\');

        if (! str_starts_with($namespace.'\\', $app_namespace.'\\')) {
            throw new InvalidArgumentException(
                "[".self::DISCOVERY_KEY."] is [{$namespace}]; it must sit under the application namespace [{$app_namespace}]."
            );
        }

        return $this->app->path(str_replace('\\', DIRECTORY_SEPARATOR, substr($namespace, strlen($app_namespace) + 1)));
    }
}
