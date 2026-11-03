<?php

namespace Fabricate\Core\Providers;

use Fabricate\Client\ClientRegistry;
use Fabricate\Client\DiscoverClients;
use Fabricate\Core\Routing\ProcedureRouter;
use Fabricate\Server\DiscoverServers;
use Fabricate\Server\ServerRegistry;
use Fabricate\Server\DiscoverPrompts;
use Fabricate\Server\DiscoverTools;
use Fabricate\Server\PromptRegistry;
use Fabricate\Server\DiscoverResources;
use Fabricate\Server\DiscoverResourceTemplates;
use Fabricate\Server\ResourceRegistry;
use Fabricate\Server\ResourceTemplateRegistry;
use Fabricate\Server\ToolRegistry;
use Fabricate\Core\Capabilities\Tools;
use InvalidArgumentException;
use Voyager\Contracts\Core\FrameworkCore;
use Voyager\NutsAndBolts\ServiceProvider;

class SuperconductorMCPServiceProvider extends ServiceProvider
{
    /** @var list<class-string<\Fabricate\Mechanism\Tool>> */
    private const array CAPABILITY_TOOLS = [
        Tools\ListToolsTool::class,
        Tools\SearchToolsTool::class,
        Tools\ExecuteToolTool::class,
        Tools\SearchPromptsTool::class,
        Tools\ListResourcesTool::class,
        Tools\SearchResourcesTool::class,
        Tools\ListResourceTemplatesTool::class,
        Tools\SearchResourceTemplatesTool::class,
    ];

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../../../config/mcp.php', 'mcp');

        $this->app->registerSingleton(ProcedureRouter::class);

        $this->app->registerSingleton('mcp.servers', fn (FrameworkCore $app) => new ServerRegistry($app));
        $this->app->alias('mcp.servers', ServerRegistry::class);

        $this->app->registerSingleton('mcp.clients', fn (FrameworkCore $app) => new ClientRegistry($app));
        $this->app->alias('mcp.clients', ClientRegistry::class);

        $this->app->registerSingleton('mcp.tools', fn (FrameworkCore $app) => new ToolRegistry($app));
        $this->app->alias('mcp.tools', ToolRegistry::class);

        $this->app->registerSingleton('mcp.prompts', fn (FrameworkCore $app) => new PromptRegistry($app));
        $this->app->alias('mcp.prompts', PromptRegistry::class);

        $this->app->registerSingleton('mcp.resources', fn (FrameworkCore $app) => new ResourceRegistry($app));
        $this->app->alias('mcp.resources', ResourceRegistry::class);

        $this->app->registerSingleton('mcp.resource-templates', fn (FrameworkCore $app) => new ResourceTemplateRegistry($app));
        $this->app->alias('mcp.resource-templates', ResourceTemplateRegistry::class);
    }

    public function boot(): void
    {
        $package_routes = __DIR__.'/../../../../routes/mcp.php';
        $app_routes = $this->app->basePath('routes/mcp.php');

        $this->publishes([$package_routes => $app_routes], 'mcp-routes');

        require is_file($app_routes) ? $app_routes : $package_routes;

        $this->discoverEntities();
    }

    private function discoverEntities(): void
    {
        $servers = $this->app->make(ServerRegistry::class);
        $namespace = $this->discoveryNamespace('mcp.servers.discovery_namespace');

        foreach (DiscoverServers::within($this->discoveryPath($namespace), $namespace) as $class) {
            $servers->register($class);
        }

        $clients = $this->app->make(ClientRegistry::class);
        $namespace = $this->discoveryNamespace('mcp.clients.discovery_namespace');

        foreach (DiscoverClients::within($this->discoveryPath($namespace), $namespace) as $class) {
            $clients->register($class);
        }

        $tools = $this->app->make(ToolRegistry::class);

        // The capability tools are always registered; a server offers one by naming it in tools().
        foreach (self::CAPABILITY_TOOLS as $class) {
            $tools->register($class);
        }

        $namespace = $this->discoveryNamespace('mcp.tools.discovery_namespace');

        foreach (DiscoverTools::within($this->discoveryPath($namespace), $namespace) as $class) {
            $tools->register($class);
        }

        $prompts = $this->app->make(PromptRegistry::class);
        $namespace = $this->discoveryNamespace('mcp.prompts.discovery_namespace');

        foreach (DiscoverPrompts::within($this->discoveryPath($namespace), $namespace) as $class) {
            $prompts->register($class);
        }

        $resources = $this->app->make(ResourceRegistry::class);
        $templates = $this->app->make(ResourceTemplateRegistry::class);
        $namespace = $this->discoveryNamespace('mcp.resources.discovery_namespace');

        foreach (DiscoverResources::within($this->discoveryPath($namespace), $namespace) as $class) {
            $resources->register($class);
        }

        foreach (DiscoverResourceTemplates::within($this->discoveryPath($namespace), $namespace) as $class) {
            $templates->register($class);
        }
    }

    private function discoveryNamespace(string $key): string
    {
        $namespace = $this->app['config']->get($key);

        if (! is_string($namespace) || $namespace === '') {
            throw new InvalidArgumentException("[{$key}] must name the namespace to discover configurations in.");
        }

        return trim($namespace, '\\');
    }

    // Configurations live in the application's own PSR-4 tree, so the folder follows from the namespace.
    private function discoveryPath(string $namespace): string
    {
        $app_namespace = trim($this->app->getNamespace(), '\\');

        if (! str_starts_with($namespace.'\\', $app_namespace.'\\')) {
            throw new InvalidArgumentException("[{$namespace}] must sit under the application namespace [{$app_namespace}].");
        }

        return $this->app->path(str_replace('\\', DIRECTORY_SEPARATOR, substr($namespace, strlen($app_namespace) + 1)));
    }
}
