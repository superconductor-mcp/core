<?php

namespace Fabricate\Server;

use Fabricate\Mechanism\MCPEntity;
use Fabricate\Contracts\NutsAndBolts\LogLevel;
use Fabricate\NutsAndBolts\Messages\MCPNotification;
use Fabricate\NutsAndBolts\Messages\Logging\LoggingMessageNotification;
use Fabricate\Contracts\Server\MCPServer as ServerContract;

abstract class MCPServer extends MCPEntity implements ServerContract
{
    protected array $tools = [];

    /** @var list<MCPNotification> notifications queued for the transport to send after the current message */
    protected array $outbox = [];

    /** @var list<\Fabricate\NutsAndBolts\PromptDefinition> */
    protected array $prompts = [];

    /** @var list<\Fabricate\NutsAndBolts\ResourceDefinition> */
    protected array $resources = [];

    /** @var list<\Fabricate\NutsAndBolts\ResourceTemplateDefinition> */
    protected array $resource_templates = [];

    public function serverInfo(): array
    {
        return $this->entityInfo();
    }

    public function capabilities(): array
    {
        return $this->entity_capabilities;
    }

    public function tools(): array
    {
        return $this->tools;
    }

    public function prompts(): array
    {
        return $this->prompts;
    }

    public function resources(): array
    {
        return $this->resources;
    }

    public function resourceTemplates(): array
    {
        return $this->resource_templates;
    }

    // a notification that depends on a capability is only queued when this server advertises that capability
    public function notify(MCPNotification $notification): void
    {
        if ($this->advertises($notification)) {
            $this->outbox[] = $notification;
        }
    }

    protected function advertises(MCPNotification $notification): bool
    {
        $capabilities = $this->capabilities();

        return match ($notification->method) {
            'notifications/tools/list_changed' => ($capabilities['tools']['listChanged'] ?? false) === true,
            'notifications/resources/list_changed' => ($capabilities['resources']['listChanged'] ?? false) === true,
            'notifications/resources/updated' => ($capabilities['resources']['subscribe'] ?? false) === true,
            'notifications/message' => array_key_exists('logging', $capabilities),
            'notifications/prompts/list_changed' => ($capabilities['prompts']['listChanged'] ?? false) === true,
            default => true,
        };
    }

    // send a log entry to the client; each session's level decides whether it actually goes out
    public function log(LogLevel $level, mixed $data, ?string $logger = null): void
    {
        $this->notify(new LoggingMessageNotification($level, $data, $logger));
    }

    /** @return list<MCPNotification> */
    public function drainNotifications(): array
    {
        [$drained, $this->outbox] = [$this->outbox, []];

        return $drained;
    }
}
