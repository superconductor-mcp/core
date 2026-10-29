<?php

namespace Fabricate\Core\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;
use Voyager\Console\GeneratorCommand;
use Voyager\NutsAndBolts\DataObjects\Str;

#[AsCommand(name: 'make:mcp-server')]
class MakeServerCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected ?string $name = 'make:mcp-server';

    /**
     * The console command description.
     *
     * @var string
     */
    protected string $description = 'Create a new MCP server class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected ?string $type = 'MCP server';

    /**
     * Get the stub file for the generator: the application's own stubs/mcp-server.stub, else the package's.
     *
     * @return string
     */
    protected function getStub(): string
    {
        return file_exists($custom = $this->venusian->basePath('stubs/mcp-server.stub'))
            ? $custom
            : __DIR__.'/../../../../stubs/mcp-server.stub';
    }

    /**
     * Build the class; the server's name is the kebab-cased class name, as ServerRepository names it.
     *
     * @param  string  $name
     * @return string
     */
    protected function buildClass(string $name): string
    {
        return str_replace('{{ server_name }}', Str::kebab(class_basename($name)), parent::buildClass($name));
    }

    /**
     * Get the default namespace for the class: where server discovery looks.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace(string $rootNamespace): string
    {
        return trim((string) config('mcp.servers.default_discovery_namespace', 'App\\Mcp\\Servers'), '\\');
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions(): array
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the MCP server already exists'],
        ];
    }
}
