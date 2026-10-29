<?php

namespace Fabricate\Client;

use Voyager\Contracts\Core\FrameworkCore;
use Voyager\NutsAndBolts\ServiceProvider;

class ClientServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->registerSingleton('mcp.clients', fn (FrameworkCore $app) => new ClientManager($app));
        $this->app->alias('mcp.clients', ClientManager::class);
    }
}
