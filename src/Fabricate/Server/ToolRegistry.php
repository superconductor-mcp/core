<?php

namespace Fabricate\Server;

use InvalidArgumentException;
use Fabricate\Contracts\Core\SuperconductorMCPException;
use Fabricate\Contracts\Mechanism\Attributes\ToolCall;
use Fabricate\Contracts\Schema\RPCErrorCode;
use Fabricate\Mechanism\EntityRegistry;
use Fabricate\Mechanism\Tool;

class ToolRegistry extends EntityRegistry
{
    protected static string $attribute = ToolCall::class;

    protected array $tools = [];

    protected string $subclass = Tool::class;

    protected string $subclass_label = "Tool";

    protected string $binding_prefix = 'mcp.tool.';

    /**
     * The class of a registered tool. A tool is built per call from its arguments, so the registry hands back the class, not an instance.
     *
     * @return class-string<Tool>
     */
    public function tool(string $name): string
    {
        if(! $this->has($name)) {
            throw new SuperconductorMCPException("Unknown tool [{$name}].", RPCErrorCode::INTERNAL_ERROR->value);
        }

        return $this->tools[$name];
    }

    protected function entity(): array
    {
        return $this->tools;
    }

    /**
     * Registers a tool whose name follows the MCP tool name rules: 1 to 128 characters, each a letter, digit,
     * underscore, hyphen or dot.
     */
    protected function setEntity(string $name, string $class): void
    {
        if(! preg_match('/^[A-Za-z0-9_.-]{1,128}$/', $name)) {
            throw new InvalidArgumentException("Tool [{$class}] is named [{$name}]; a tool name is 1 to 128 letters, digits, underscores, hyphens or dots.");
        }

        $this->tools[$name] = $class;
    }
}
