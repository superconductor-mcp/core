<?php

namespace Fabricate\NutsAndBolts\Messages;

abstract readonly class RoutedMCPMessage  extends MCPMessage
{
    public function __construct(
        public string $method,
        public ?array $params = null,
    ) {
        parent::__construct();
    }
}