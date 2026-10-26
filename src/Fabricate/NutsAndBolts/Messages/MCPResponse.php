<?php

namespace Fabricate\NutsAndBolts\Messages;

abstract readonly class MCPResponse extends MCPMessage
{
    public function __construct(
        public string|int|null $id,
        public ?array $result = null,
        public ?array $error = null
    ) {
        parent::__construct();
    }

    abstract public function toJson(): string;
}