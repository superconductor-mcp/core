<?php

namespace Fabricate\Mechanism;

use Fabricate\Contracts\Mechanism\EntityCapabilities as CapabilitiesContract;

abstract class EntityCapabilities implements CapabilitiesContract
{
    public function supportsCapability(string $name): bool
    {
        return isset($this->$name);
    }

    public function offersCapability(string $path): bool
    {
        $keys = explode('.', $path);
        $name = array_shift($keys);

        // A version with no declaration for this, like completions in 2024-11-05, has nothing to withhold.
        if(!property_exists($this, $name)) return true;

        if(!$this->supportsCapability($name)) return false;

        $payload = $this->$name;

        foreach($keys as $key)
        {
            if(!is_array($payload) || !array_key_exists($key, $payload)) return false;

            $payload = $payload[$key];
        }

        return true;
    }
}
