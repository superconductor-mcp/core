<?php

namespace Fabricate\NutsAndBolts\Messages\Tools;

use Fabricate\NutsAndBolts\Messages\MCPNotification;

// sent by a server that advertised tools.listChanged whenever the tools it offers change
final readonly class ToolListChangedNotification extends MCPNotification
{
    public function __construct()
    {
        parent::__construct('notifications/tools/list_changed');
    }

    public function toJson(): string
    {
        // a notification carries no id and, for this one, no params
        return json_encode([
            'jsonrpc' => '2.0',
            'method' => $this->method,
        ]);
    }
}
