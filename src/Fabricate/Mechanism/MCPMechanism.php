<?php

namespace Fabricate\Mechanism;

use Fabricate\Contracts\Mechanism\Mechanism as MechanismContract;

abstract class MCPMechanism implements MechanismContract
{
    protected array $entity_info = [];
    protected array $entity_capabilities = [];

    public function entityInfo(): array
    {
        return $this->entity_info;
    }
}