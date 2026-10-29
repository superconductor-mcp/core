<?php

namespace Fabricate\Core\Providers;

use ReflectionException;
use Voyager\NutsAndBolts\ServiceProvider;
use Fabricate\Client\ClientServiceProvider;
use Fabricate\Core\Commands\MakeServerCommand;
use Fabricate\Server\ServerServiceProvider;

class SuperconductorMCPServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../../../config/mcp.php', 'mcp');

        $this->app->register(ServerServiceProvider::class);
        $this->app->register(ClientServiceProvider::class);

        $this->commands([MakeServerCommand::class]);
    }

    /**
     * @throws ReflectionException
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../../../../config/mcp.php' => config_path('mcp.php'),
        ], 'mcp-config');
    }
}
