<?php

namespace Fabricate\NutsAndBolts;

use Fabricate\Server\MCPServer;
use Fabricate\NutsAndBolts\Messages\MCPNotification;
use Fabricate\NutsAndBolts\Messages\Resources\ResourceUpdatedNotification;
use Fabricate\NutsAndBolts\Messages\Logging\LoggingMessageNotification;
use Voyager\Workflows\SharedBag;

class MCPServerBag extends SharedBag
{
    public ?MCPServer $server_configuration = null;
    public array $notifications = [];

    /** @var array<string, true> uris this session's client subscribed to with resources/subscribe */
    public array $subscriptions = [];

    // the minimum level this session's client receives; null until set, which falls back to mcp.logging.default_level
    public ?LogLevel $log_level = null;

    /** @var list<float> when this session's recent completion/complete calls arrived, for the rate limit */
    public array $completion_calls = [];

    // what reaches this session's client: resources/updated only for subscribed uris, log messages only at its level or above
    public function wants(MCPNotification $notification): bool
    {
        return match (true) {
            $notification instanceof ResourceUpdatedNotification => isset($this->subscriptions[$notification->uri]),
            $notification instanceof LoggingMessageNotification => $notification->level->atLeast($this->logLevel()),
            default => true,
        };
    }

    public function logLevel(): LogLevel
    {
        return $this->log_level ?? LogLevel::tryFrom((string) config('mcp.logging.default_level', 'info')) ?? LogLevel::Info;
    }
}