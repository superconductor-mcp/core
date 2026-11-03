<?php

namespace Fabricate\Server;

use Fabricate\Contracts\Mechanism\Attributes\ServerConfig;
use Fabricate\Mechanism\EntityConfiguration;
use Fabricate\Contracts\Schema\Messaging\ClientRequest;
use Fabricate\Contracts\Schema\Messaging\Initialize\InitializeResult;
use Fabricate\Contracts\Schema\LoggingLevel;
use Fabricate\Server\Tasks\TaskStore;
use Fabricate\Contracts\Schema\ProtocolVersion;
use Fabricate\Schema\V20241105;
use Fabricate\Schema\V20250326;
use Fabricate\Schema\V20250618;
use Fabricate\Schema\V20251125;
use Fabricate\Contracts\Server\ServerConfiguration as ConfigurationContract;
use JetBrains\PhpStorm\Deprecated;

abstract class ServerConfiguration extends EntityConfiguration implements ConfigurationContract
{
    /** The 2026-07-28 tasks extension's identifier, in capabilities and per-request client capabilities. */
    public const string TASKS_EXTENSION = 'io.modelcontextprotocol/tasks';

    protected bool|array $tasks = false;
    protected bool|array $tools = false;
    protected bool $completions = false;
    protected bool|array $prompts = false;
    protected bool|array $resources = false;
    protected bool|array $extensions = false;
    protected bool|array $experimental = false;
    #[Deprecated] protected bool $logging = false;

    protected string $accepted_request = ClientRequest::class;

    protected array $capabilities = [
        'tasks', 'tools', 'completions', 'prompts', 'resources', 'extensions', 'experimental', 'logging',
    ];

    protected array $capability_arguments = [
        'tools' => 'tools',
        'tools.listChanged' => 'announce_when_tools_change',
        'resources' => 'resources',
        'resources.subscribe' => 'announce_individual_resource_updates',
        'resources.listChanged' => 'announce_when_resources_change',
        'prompts' => 'prompts',
        'prompts.listChanged' => 'announce_when_prompts_change',
        'completions' => 'completions',
        'logging' => 'logging',
        'tasks' => 'tasks',
        'tasks.list' => 'support_tasks_list',
        'tasks.cancel' => 'support_tasks_cancel',
        'tasks.requests' => 'support_tasks_requests',
        'tasks.requests.tools' => 'support_tasks_tool_requests',
        'tasks.requests.tools.call' => 'support_tasks_tool_call_requests',
    ];

    protected string $attribute = ServerConfig::class;

    /** The level the client last asked for with logging/setLevel; null until it asks. */
    protected ?LoggingLevel $log_level = null;

    /** @var array<string, true> The resource URIs the session subscribed to with resources/subscribe. */
    protected array $subscriptions = [];

    /** The session's tasks, made the first time a request runs as one. */
    protected ?TaskStore $task_store = null;

    public function __construct(
        ServerCapabilitiesFactory $capabilities_factory
    ) {
        parent::__construct($capabilities_factory);
    }

    /**
     * Builds the result this server answers an initialize request with, in the version handshakeProtocolVersion()
     * settles on for the one the client requested.
     */
    public function initializeResult(string $requested_protocol_version): InitializeResult
    {
        $version = $this->handshakeProtocolVersion($requested_protocol_version);
        $capabilities = $this->capabilities($version);
        $server_info = $this->implementation($version);
        $instructions = $this->instructions();

        return match (ProtocolVersion::from($version)) {
            ProtocolVersion::V20241105 => new V20241105\Messaging\Initialize\InitializeResult($version, $capabilities, $server_info, $instructions),
            ProtocolVersion::V20250326 => new V20250326\Messaging\Initialize\InitializeResult($version, $capabilities, $server_info, $instructions),
            ProtocolVersion::V20250618 => new V20250618\Messaging\Initialize\InitializeResult($version, $capabilities, $server_info, $instructions),
            ProtocolVersion::V20251125 => new V20251125\Messaging\Initialize\InitializeResult($version, $capabilities, $server_info, $instructions),
        };
    }

    /**
     * How to use the server, from #[ServerConfig]: sent with the initialize result, and from 2026-07-28 with server/discover.
     */
    public function instructions(): ?string
    {
        return $this->attributeInstance()?->instructions;
    }

    /**
     * From 2026-07-28 tasks are the io.modelcontextprotocol/tasks extension rather than a capability of their own: a
     * configuration that runs tools/call as tasks declares the extension.
     */
    protected function capabilityArgumentsFor(string $protocol_version): array
    {
        $arguments = parent::capabilityArgumentsFor($protocol_version);

        if($protocol_version === '2026-07-28' && ($arguments['support_tasks_tool_call_requests'] ?? false)) {
            $arguments['extensions'] = [self::TASKS_EXTENSION => [], ...($arguments['extensions'] ?? [])];
        }

        return $arguments;
    }

    public function logLevel(): ?LoggingLevel
    {
        return $this->log_level;
    }

    public function setLogLevel(LoggingLevel $level): void
    {
        $this->log_level = $level;
    }

    public function subscribe(string $uri): void
    {
        $this->subscriptions[$uri] = true;
    }

    public function unsubscribe(string $uri): void
    {
        unset($this->subscriptions[$uri]);
    }

    public function subscribedTo(string $uri): bool
    {
        return isset($this->subscriptions[$uri]);
    }

    public function taskStore(): TaskStore
    {
        return $this->task_store ??= new TaskStore();
    }

    /**
     * Whether this configuration declared listChanged for a capability: tools, prompts or resources.
     */
    public function announcesListChanges(string $capability): bool
    {
        return (bool) ($this->capabilityConfig($capability)['listChanged'] ?? false);
    }

    // What each capability exposes. A concrete overrides the ones it declares; the rest expose nothing.
    public function tasks(mixed ...$params): array { return []; }
    public function tools(mixed ...$params): array { return []; }
    public function completions(mixed ...$params): array { return []; }
    public function prompts(mixed ...$params): array { return []; }
    public function resources(mixed ...$params): array { return []; }
    public function extensions(mixed ...$params): array { return []; }
    public function experimental(mixed ...$params): array { return []; }
    public function logging(mixed ...$params): array { return []; }

    public function tasksConfig(): ?array { return $this->capabilityConfig('tasks'); }
    public function toolsConfig(): ?array { return $this->capabilityConfig('tools'); }
    public function completionsConfig(): ?array { return $this->capabilityConfig('completions'); }
    public function promptsConfig(): ?array { return $this->capabilityConfig('prompts'); }
    public function resourcesConfig(): ?array { return $this->capabilityConfig('resources'); }
    public function extensionsConfig(): ?array { return $this->capabilityConfig('extensions'); }
    public function experimentalConfig(): ?array { return $this->capabilityConfig('experimental'); }
    public function loggingConfig(): ?array { return $this->capabilityConfig('logging'); }
}
