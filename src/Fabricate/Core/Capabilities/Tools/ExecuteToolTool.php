<?php

namespace Fabricate\Core\Capabilities\Tools;

use Error;
use Fabricate\Contracts\Core\SuperconductorMCPException;
use Fabricate\Contracts\Mechanism\Attributes\ToolCall;
use Fabricate\Contracts\Server\ServerConfiguration;
use Fabricate\Mechanism\Completion;
use Fabricate\Mechanism\Invoker;
use Fabricate\Mechanism\RequestContext;
use Fabricate\Mechanism\Tool;
use Fabricate\Server\ToolRegistry;
use Throwable;

/**
 * Runs another tool the server offers, by name. When it runs async and the tool it runs does too, it waits for that
 * tool by mail instead of holding the loop: the other tool's outcome becomes its own.
 */
#[ToolCall('execute_tool', 'Run one of the tools this server offers, by name, with its arguments.')]
final class ExecuteToolTool extends Tool
{
    protected static string $task_support = self::TASKS_OPTIONAL;

    protected bool $async = true;

    /**
     * @param array<string, mixed> $arguments The tool's arguments, by name.
     */
    public function __construct(
        public readonly string $name,
        public readonly array $arguments = [],
    ) {}

    public function handle(ServerConfiguration $server, ToolRegistry $tools, string $protocol_version, ?RequestContext $context = null): mixed
    {
        if($this->name === static::name() || ! in_array($this->name, $server->tools(), true)) {
            throw new SuperconductorMCPException("Unknown tool: [{$this->name}].");
        }

        $class = $tools->tool($this->name);

        try {
            $tool = new $class(...$this->arguments);
        } catch(Error $e) {
            throw new SuperconductorMCPException("Invalid arguments for tool [{$this->name}]: ".Invoker::argumentError($e));
        }

        if(! $this->isAsync()) return Invoker::call($tool, 'handle', $server, $protocol_version, [], $context);

        // The tool it runs reports to this one, which reports to the procedure.
        if($tool->wantsAsync()) $tool->deferTo(new Completion(
            fn(mixed $output) => $output,
            fn(Throwable $e) => $e,
            fn(mixed $outcome) => $outcome instanceof Throwable ? $this->fail($outcome) : $this->finish($outcome),
        ));

        try {
            $output = Invoker::call($tool, 'handle', $server, $protocol_version, [], $context);

            if(! $tool->wantsAsync()) $this->finish($output);
        } catch(Throwable $e) {
            $this->fail($e);
        }

        return null;
    }
}
