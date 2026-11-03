<?php

namespace Fabricate\Mechanism;

use Fabricate\Contracts\Mechanism\Attributes\Argument;
use Fabricate\Contracts\Mechanism\Attributes\ToolAnnotations;
use Fabricate\Contracts\Mechanism\Attributes\ToolCall;
use Fabricate\Mechanism\Concerns\DescribedByAttributes;
use Fabricate\Contracts\Mechanism\MCPMechanismException;
use Fabricate\Contracts\Mechanism\Tool as ToolContract;
use Fabricate\Mechanism\Concerns\RunsAsync;
use Fabricate\Mechanism\Concerns\SignalsChanges;
use Fabricate\Mechanism\Signals\ListChanged;
use ReflectionClass;
use ReflectionNamedType;

abstract class Tool implements ToolContract
{
    use RunsAsync, SignalsChanges, DescribedByAttributes;

    public const string TASKS_FORBIDDEN = 'forbidden';
    public const string TASKS_OPTIONAL = 'optional';
    public const string TASKS_REQUIRED = 'required';

    /** Whether a 2025-11-25 client may run this tool as a task: forbidden, optional or required. */
    protected static string $task_support = self::TASKS_FORBIDDEN;

    public static function taskSupport(): string
    {
        return static::$task_support;
    }

    public static function name(): string
    {
        return static::toolCall()->name;
    }

    public static function description(): ?string
    {
        return static::toolCall()->description;
    }

    public static function title(): ?string
    {
        return static::toolCall()->title;
    }

    public static function annotations(): ?ToolAnnotations
    {
        return static::classAttribute(ToolAnnotations::class);
    }

    /**
     * The JSON Schema the tool's structured result conforms to, from 2025-06-18: each property's schema by name, and
     * the names the result must have. A tool that declares one returns an associative array from handle(), which goes
     * out as structuredContent and, serialized, as text. None by default.
     *
     * @return array{properties: array<string, array<string, mixed>>, required: list<string>}|null
     */
    public static function outputSchema(): ?array
    {
        return null;
    }

    /**
     * The constructor is the input schema: each parameter is an argument, and those without a default are required.
     *
     * @return array{properties: array<string, array<string, mixed>>, required: list<string>}
     */
    public static function inputSchema(): array
    {
        $properties = [];
        $required = [];

        foreach(new ReflectionClass(static::class)->getConstructor()?->getParameters() ?? [] as $parameter)
        {
            $type = $parameter->getType();
            $json_type = $type instanceof ReflectionNamedType ? match($type->getName()) {
                'string' => 'string',
                'int' => 'integer',
                'float' => 'number',
                'bool' => 'boolean',
                'array' => 'object',
                default => null,
            } : null;

            // An argument of a type JSON Schema can't name accepts any value.
            $schema = is_null($json_type) ? [] : ['type' => $json_type];
            $argument = ($parameter->getAttributes(Argument::class)[0] ?? null)?->newInstance();

            if($argument?->title) $schema['title'] = $argument->title;
            if($argument?->description) $schema['description'] = $argument->description;

            $properties[$parameter->getName()] = $schema;

            if(! $parameter->isOptional()) $required[] = $parameter->getName();
        }

        return ['properties' => $properties, 'required' => $required];
    }

    /**
     * The arguments a Streamable HTTP call mirrors into Mcp-Param-{header} headers: each header name by argument name.
     * A header name is an HTTP token, unique among the tool's ignoring case, on an int, string or bool argument.
     *
     * @return array<string, string>
     */
    public static function mirroredHeaders(): array
    {
        $headers = [];

        foreach(new ReflectionClass(static::class)->getConstructor()?->getParameters() ?? [] as $parameter)
        {
            $header = ($parameter->getAttributes(Argument::class)[0] ?? null)?->newInstance()->header;

            if(is_null($header)) continue;

            $type = $parameter->getType();
            $where = static::class." argument [{$parameter->getName()}]";

            if(! preg_match("/^[!#$%&'*+\\-.^_`|~0-9A-Za-z]+$/", $header)) {
                throw new MCPMechanismException("{$where} names header [{$header}]; a header name is one or more HTTP token characters.");
            }

            if(! $type instanceof ReflectionNamedType || ! in_array($type->getName(), ['int', 'string', 'bool'], true)) {
                throw new MCPMechanismException("{$where} names header [{$header}]; only int, string and bool arguments are mirrored into headers.");
            }

            if(in_array(strtolower($header), array_map(strtolower(...), $headers), true)) {
                throw new MCPMechanismException("{$where} names header [{$header}], which another argument already names.");
            }

            $headers[$parameter->getName()] = $header;
        }

        return $headers;
    }

    protected static function listCapability(): string
    {
        return ListChanged::TOOLS;
    }

    private static function toolCall(): ToolCall
    {
        $attributes = new ReflectionClass(static::class)->getAttributes(ToolCall::class);

        if($attributes === []) {
            throw new MCPMechanismException(static::class.' needs a #[ToolCall] attribute to be a tool.');
        }

        return $attributes[0]->newInstance();
    }
}
