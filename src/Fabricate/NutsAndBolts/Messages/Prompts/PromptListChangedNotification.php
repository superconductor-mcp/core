<?php

namespace Fabricate\NutsAndBolts\Messages\Prompts;

use Fabricate\NutsAndBolts\Messages\MCPNotification;

// sent by a server that advertised prompts.listChanged whenever the prompts it offers change
final readonly class PromptListChangedNotification extends MCPNotification
{
    public function __construct()
    {
        parent::__construct('notifications/prompts/list_changed');
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
