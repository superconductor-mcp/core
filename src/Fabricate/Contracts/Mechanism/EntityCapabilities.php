<?php

namespace Fabricate\Contracts\Mechanism;

interface EntityCapabilities
{
    public function offersCapability(string $path): bool;
}
