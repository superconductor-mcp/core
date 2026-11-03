<?php

namespace Fabricate\Mechanism;

use Fabricate\Contracts\Mechanism\Attributes\Argument;
use Fabricate\Contracts\Mechanism\Attributes\PromptCall;
use Fabricate\Mechanism\Concerns\DescribedByAttributes;
use Fabricate\Contracts\Mechanism\MCPMechanismException;
use Fabricate\Contracts\Mechanism\Prompt as PromptContract;
use Fabricate\Mechanism\Concerns\RunsAsync;
use Fabricate\Mechanism\Concerns\SignalsChanges;
use Fabricate\Mechanism\Signals\ListChanged;
use ReflectionClass;

abstract class Prompt implements PromptContract
{
    use RunsAsync, SignalsChanges, DescribedByAttributes;

    public static function name(): string
    {
        return static::promptCall()->name;
    }

    public static function description(): ?string
    {
        return static::promptCall()->description;
    }

    public static function title(): ?string
    {
        return static::promptCall()->title;
    }

    /**
     * The constructor is the argument list: each parameter is an argument, those without a default are required, and
     * an #[Argument] gives one its description and title.
     *
     * @return list<array{name: string, required: bool, description: ?string, title: ?string}>
     */
    public static function arguments(): array
    {
        return array_map(function($parameter) {
            $argument = ($parameter->getAttributes(Argument::class)[0] ?? null)?->newInstance();

            return ['name' => $parameter->getName(), 'required' => ! $parameter->isOptional(), 'description' => $argument?->description, 'title' => $argument?->title];
        }, new ReflectionClass(static::class)->getConstructor()?->getParameters() ?? []);
    }

    /**
     * No suggestions is a valid answer; a prompt overrides this to offer some. Static, because there are no arguments to build the prompt from yet.
     *
     * @param array<string, string> $context Arguments already filled in, by name.
     */
    public static function complete(string $argument, string $value, array $context = []): CompletionResult
    {
        return new CompletionResult();
    }

    protected static function listCapability(): string
    {
        return ListChanged::PROMPTS;
    }

    private static function promptCall(): PromptCall
    {
        $attributes = new ReflectionClass(static::class)->getAttributes(PromptCall::class);

        if($attributes === []) {
            throw new MCPMechanismException(static::class.' needs a #[PromptCall] attribute to be a prompt.');
        }

        return $attributes[0]->newInstance();
    }
}
