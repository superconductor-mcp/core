<?php

namespace Fabricate\Core\Capabilities\Tools;

use Fabricate\Contracts\Server\ServerConfiguration;
use Fabricate\Server\PromptRegistry;
use Fabricate\Server\ResourceRegistry;
use Fabricate\Server\ResourceTemplateRegistry;
use Fabricate\Server\ToolRegistry;

/**
 * What a server offers, as plain definitions the capability tools hand a model as JSON: the same fields the list
 * methods send, without any one protocol version's extras.
 */
final readonly class Catalog
{
    public function __construct(
        private ToolRegistry $tools,
        private PromptRegistry $prompts,
        private ResourceRegistry $resources,
        private ResourceTemplateRegistry $templates,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function tools(ServerConfiguration $server, string $query = ''): array
    {
        return $this->matching(array_map(function(string $name) {
            $tool = $this->tools->tool($name);
            ['properties' => $properties, 'required' => $required] = $tool::inputSchema();

            return ['name' => $name, 'description' => $tool::description(), 'inputSchema' => ['type' => 'object', 'properties' => (object) $properties, 'required' => $required]];
        }, $server->tools()), $query);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function prompts(ServerConfiguration $server, string $query = ''): array
    {
        return $this->matching(array_map(function(string $name) {
            $prompt = $this->prompts->prompt($name);

            return ['name' => $name, 'description' => $prompt::description(), 'arguments' => array_map(fn(array $argument) => array_filter($argument, fn(mixed $value) => ! is_null($value)), $prompt::arguments())];
        }, $server->prompts()), $query);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function resources(ServerConfiguration $server, string $query = ''): array
    {
        $resources = array_values(array_filter($server->resources(), $this->resources->has(...)));

        return $this->matching(array_map(function(string $uri) {
            $resource = $this->resources->resource($uri);

            return ['uri' => $uri, 'name' => $resource::name(), 'description' => $resource::description(), 'mimeType' => $resource::mimeType()];
        }, $resources), $query);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function templates(ServerConfiguration $server, string $query = ''): array
    {
        $templates = array_values(array_filter($server->resources(), $this->templates->has(...)));

        return $this->matching(array_map(function(string $uri_template) {
            $template = $this->templates->template($uri_template);

            return ['uriTemplate' => $uri_template, 'name' => $template::name(), 'description' => $template::description(), 'mimeType' => $template::mimeType()];
        }, $templates), $query);
    }

    /**
     * The definitions with a text field containing the query, ignoring case; all of them for an empty query.
     *
     * @param list<array<string, mixed>> $definitions
     * @return list<array<string, mixed>>
     */
    private function matching(array $definitions, string $query): array
    {
        return array_values(array_filter(
            array_map(fn(array $definition) => array_filter($definition, fn(mixed $value) => ! is_null($value)), $definitions),
            fn(array $definition) => $query === '' || array_any($definition, fn(mixed $value) => is_string($value) && stripos($value, $query) !== false),
        ));
    }
}
