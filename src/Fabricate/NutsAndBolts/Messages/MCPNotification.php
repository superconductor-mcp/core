<?php

namespace Fabricate\NutsAndBolts\Messages;

use Fabricate\Contracts\NutsAndBolts\MCPNotification as NotificationContract;

abstract readonly class MCPNotification extends RoutedMCPMessage implements NotificationContract
{
    public function __construct(
        string $method,
        ?array $params = null,
    ) {
        parent::__construct(
            $method,
            $params,
        );
    }
}
