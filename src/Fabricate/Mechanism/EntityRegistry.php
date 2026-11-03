<?php

namespace Fabricate\Mechanism;

use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use Voyager\Contracts\Vessel\TheServiceContainer;
use Fabricate\Contracts\Mechanism\EntityConfiguration;
use Fabricate\Contracts\Mechanism\MCPMechanismException;
use Fabricate\Contracts\Mechanism\EntityRegistry as RegistryContract;
use Voyager\NutsAndBolts\Str;

abstract class EntityRegistry implements RegistryContract
{
    /**
     * @var string
     */
    protected static string $attribute;

    /**
     * @var string
     */
    protected string $subclass;

    /**
     * @var string
     */
    protected string $subclass_label;

    /**
     * @var string
     */
    protected string $binding_prefix;

    public function __construct(
        protected readonly TheServiceContainer $container
    ) {
        $this->validateInstance();
    }

    /**
     * @return array
     */
    abstract protected function entity(): array;

    /**
     * @param string $name
     * @param string $class
     * @return void
     */
    abstract protected function setEntity(string $name, string $class): void;

    /**
     * @return array<string, class-string<EntityConfiguration>>
     */
    public function all(): array
    {
        return $this->entity();
    }

    /**
     * @param string $name
     * @return bool
     */
    public function has(string $name): bool
    {
        $entities = $this->entity();
        return isset($entities[$name]);
    }

    /**
     * @param string $name
     * @return EntityConfiguration
     */
    public function get(string $name): EntityConfiguration
    {
        if (! $this->has($name)) {
            $known = $this->entity() === []
                ? "no {$this->subclass_label}s are registered"
                : "registered {$this->subclass_label}s: ".implode(', ', array_keys($this->entity()));

            throw new InvalidArgumentException("Unknown {$this->subclass_label} [{$name}]; {$known}.");
        }

        return $this->container->make($this->binding_prefix.$name);
    }

    /**
     * @param string $class
     * @return void
     * @throws ReflectionException
     */
    public function register(string $class): void
    {
        if (! is_subclass_of($class, $this->subclass) || new ReflectionClass($class)->isAbstract()) {
            throw new InvalidArgumentException("[{$class}] is not a concrete {$this->subclass_label}.");
        }

        $name = static::nameFor($class);
        $entities = $this->entity();

        if (isset($entities[$name]) && $entities[$name] !== $class) {
            throw new InvalidArgumentException(
                "{$this->subclass_label} [{$name}] is already registered by [{$entities[$name]}]; [{$class}] needs another name."
            );
        }

        $this->setEntity($name, $class);
        $this->container->bind($this->binding_prefix.$name, $class);
    }

    /**
     * @return void
     */
    private function validateInstance(): void
    {
        if(!isset($this->binding_prefix)) {
            throw new MCPMechanismException('The binding prefix is not defined.');
        }

        if(!isset($this->subclass)) {
            throw new MCPMechanismException('The subclass is not defined.');
        }

        if(!isset($this->subclass_label)) {
            throw new MCPMechanismException('The subclass_label is not defined.');
        }

        if(!isset(static::$attribute)) {
            throw new MCPMechanismException('The attribute is not defined.');
        }
    }

    /**
     * @param string $class
     * @return string
     * @throws ReflectionException
     */
    public static function nameFor(string $class): string
    {
        $attributes = new ReflectionClass($class)->getAttributes(static::$attribute);

        return $attributes === []
            ? Str::kebab(class_basename($class))
            : $attributes[0]->newInstance()->name;
    }
}
