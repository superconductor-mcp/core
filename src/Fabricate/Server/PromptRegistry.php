<?php

namespace Fabricate\Server;

use Fabricate\Contracts\Core\SuperconductorMCPException;
use Fabricate\Contracts\Mechanism\Attributes\PromptCall;
use Fabricate\Contracts\Schema\RPCErrorCode;
use Fabricate\Mechanism\EntityRegistry;
use Fabricate\Mechanism\Prompt;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionNamedType;

class PromptRegistry extends EntityRegistry
{
    protected static string $attribute = PromptCall::class;

    protected array $prompts = [];

    protected string $subclass = Prompt::class;

    protected string $subclass_label = "Prompt";

    protected string $binding_prefix = 'mcp.prompt.';

    /**
     * Refuses a prompt with an argument that isn't a string: prompt arguments are strings on the wire, so any other type could never be filled.
     */
    public function register(string $class): void
    {
        foreach(new ReflectionClass($class)->getConstructor()?->getParameters() ?? [] as $parameter)
        {
            $type = $parameter->getType();

            if(! $type instanceof ReflectionNamedType || $type->getName() !== 'string') {
                throw new InvalidArgumentException("[{$class}] argument \${$parameter->getName()} must be a string: prompt arguments are strings on the wire.");
            }
        }

        parent::register($class);
    }

    /**
     * The class of a registered prompt. A prompt is built per request from its arguments, so the registry hands back the class, not an instance.
     *
     * @return class-string<Prompt>
     */
    public function prompt(string $name): string
    {
        if(! $this->has($name)) {
            throw new SuperconductorMCPException("Unknown prompt [{$name}].", RPCErrorCode::INTERNAL_ERROR->value);
        }

        return $this->prompts[$name];
    }

    protected function entity(): array
    {
        return $this->prompts;
    }

    protected function setEntity(string $name, string $class): void
    {
        $this->prompts[$name] = $class;
    }
}
