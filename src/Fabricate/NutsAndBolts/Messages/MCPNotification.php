<?php

namespace Fabricate\NutsAndBolts\Messages;

abstract readonly class MCPNotification extends RoutedMCPMessage
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