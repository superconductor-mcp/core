<?php

namespace Fabricate\Contracts\Server;

use Fabricate\Contracts\Mechanism\MCPEntity;
use Fabricate\Contracts\NutsAndBolts\LogLevel;
use Fabricate\Contracts\NutsAndBolts\MCPNotification;

interface MCPServer extends MCPEntity
{
    public function serverInfo(): array;

    public function capabilities(): array;

    public function tools(): array;

    /** @return list<\Fabricate\NutsAndBolts\PromptDefinition> */
    public function prompts(): array;

    /** @return list<\Fabricate\NutsAndBolts\ResourceDefinition> */
    public function resources(): array;

    /** @return list<\Fabricate\NutsAndBolts\ResourceTemplateDefinition> */
    public function resourceTemplates(): array;

    // queued for the transport only when this server advertises the capability the notification depends on
    public function notify(MCPNotification $notification): void;

    // send a log entry to the client; each session's level decides whether it actually goes out
    public function log(LogLevel $level, mixed $data, ?string $logger = null): void;

    /** @return list<MCPNotification> */
    public function drainNotifications(): array;
}
