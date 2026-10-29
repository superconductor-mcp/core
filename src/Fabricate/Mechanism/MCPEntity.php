<?php

namespace Fabricate\Mechanism;

use Fabricate\Contracts\Mechanism\MCPEntity as EntityContract;

abstract class MCPEntity implements EntityContract
{
    protected array $entity_info = [];
    protected array $entity_capabilities = [];

    public function entityInfo(): array
    {
        return $this->entity_info;
    }
}
