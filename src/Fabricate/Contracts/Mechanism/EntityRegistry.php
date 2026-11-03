<?php

namespace Fabricate\Contracts\Mechanism;

interface EntityRegistry
{
    /**
     * @return array
     */
    public function all(): array;

    /**
     * @param string $name
     * @return bool
     */
    public function has(string $name): bool;

    /**
     * @param string $name
     * @return EntityConfiguration
     */
    public function get(string $name): EntityConfiguration;

    /**
     * @param string $class
     * @return void
     */
    public function register(string $class): void;
}
