<?php

namespace Fabricate\Core\Providers;

use Fabricate\Client\Providers\ClientServiceProvider;
use Fabricate\Server\Providers\ServerServiceProvider;
use Voyager\NutsAndBolts\ServiceProvider;

class SuperconductorMCPServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../../../config/mcp.php', 'mcp');

        $this->app->register(ServerServiceProvider::class);
        $this->app->register(ClientServiceProvider::class);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../../../../config/mcp.php' => config_path('mcp.php'),
        ], 'mcp-config');
    }
}
