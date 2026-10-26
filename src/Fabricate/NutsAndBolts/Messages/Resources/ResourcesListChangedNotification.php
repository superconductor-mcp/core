<?php

namespace Fabricate\NutsAndBolts\Messages\Resources;

use Fabricate\NutsAndBolts\Messages\MCPNotification;

// sent by a server that advertised resources.listChanged whenever the resources it offers change
final readonly class ResourcesListChangedNotification extends MCPNotification
{
    public function __construct()
    {
        parent::__construct('notifications/resources/list_changed');
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
