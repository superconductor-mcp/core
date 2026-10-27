<?php

namespace Fabricate\Tests\Fixtures;

use Fabricate\Server\MCPServer;
use Fabricate\NutsAndBolts\ToolDefinition;
use Fabricate\NutsAndBolts\Schema\TextProperty;
use Fabricate\NutsAndBolts\Schema\StringProperty;

// a server with two tools, one of which fails, and the capabilities the tests reason about
final class BenchServer extends MCPServer
{
    protected array $entity_info = ['name' => 'bench', 'version' => '1.0'];

    protected array $entity_capabilities = [
        'tools' => ['listChanged' => true],
        'resources' => ['subscribe' => true, 'listChanged' => false],
        'logging' => [],
    ];

    public function __construct()
    {
        $this->tools = [
            new ToolDefinition('echo', 'Echoes its text', ['text' => new StringProperty()], ['text'], fn (string $text): TextProperty => new TextProperty($text)),
            new ToolDefinition('boom', 'Always fails', [], [], fn (): TextProperty => throw new \RuntimeException('kaboom')),
        ];
    }
}
