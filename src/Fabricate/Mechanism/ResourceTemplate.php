<?php

namespace Fabricate\Mechanism;

use Fabricate\Contracts\Mechanism\Attributes\ResourceAnnotations;
use Fabricate\Contracts\Mechanism\Attributes\ResourceTemplateCall;
use Fabricate\Mechanism\Concerns\DescribedByAttributes;
use Fabricate\Contracts\Mechanism\MCPMechanismException;
use Fabricate\Contracts\Mechanism\ResourceTemplate as TemplateContract;
use Fabricate\Mechanism\Concerns\RunsAsync;
use Fabricate\Mechanism\Concerns\SignalsChanges;
use Fabricate\Mechanism\Signals\ListChanged;
use Fabricate\Mechanism\Signals\ResourceUpdated;
use ReflectionClass;
use Voyager\NutsAndBolts\Str;

/**
 * The resources whose URIs fit one URI template. A concrete carries #[ResourceTemplateCall] and a read() method,
 * called through the container with $uri, each template variable by name, the server configuration and $protocol_version.
 *
 * Templates use RFC 6570 simple expansion: {name} fills one path segment, {+name} may span several.
 */
abstract class ResourceTemplate implements TemplateContract
{
    use RunsAsync, SignalsChanges, DescribedByAttributes;

    public static function uriTemplate(): string
    {
        return static::templateCall()->uriTemplate;
    }

    public static function name(): string
    {
        return static::templateCall()->name ?? Str::kebab(class_basename(static::class));
    }

    public static function description(): ?string
    {
        return static::templateCall()->description;
    }

    public static function mimeType(): ?string
    {
        return static::templateCall()->mimeType;
    }

    public static function title(): ?string
    {
        return static::templateCall()->title;
    }

    public static function annotations(): ?ResourceAnnotations
    {
        return static::classAttribute(ResourceAnnotations::class);
    }

    /**
     * @return list<string>
     */
    public static function variables(): array
    {
        preg_match_all('/\{\+?(\w+)\}/', static::uriTemplate(), $matches);

        return $matches[1];
    }

    /**
     * @return array<string, string>|null
     */
    public static function match(string $uri): ?array
    {
        $pattern = preg_replace_callback(
            '/\\\\\{(\\\\\+)?(\w+)\\\\\}/',
            fn(array $variable) => $variable[1] === '' ? "(?<{$variable[2]}>[^/?#]*)" : "(?<{$variable[2]}>[^?#]*)",
            preg_quote(static::uriTemplate(), '~'),
        );

        if(! preg_match("~^{$pattern}$~", $uri, $matches)) return null;

        return array_map(rawurldecode(...), array_intersect_key($matches, array_flip(static::variables())));
    }

    /**
     * No suggestions is a valid answer; a template overrides this to offer some.
     *
     * @param array<string, string> $context Variables already filled in, by name.
     */
    public static function complete(string $argument, string $value, array $context = []): CompletionResult
    {
        return new CompletionResult();
    }

    /**
     * Tells every session subscribed to the resource at $uri, which fits this template, that its contents changed.
     */
    public static function updated(string $uri): void
    {
        signal(new ResourceUpdated($uri));
    }

    protected static function listCapability(): string
    {
        return ListChanged::RESOURCES;
    }

    private static function templateCall(): ResourceTemplateCall
    {
        $attributes = new ReflectionClass(static::class)->getAttributes(ResourceTemplateCall::class);

        if($attributes === []) {
            throw new MCPMechanismException(static::class.' needs a #[ResourceTemplateCall] attribute to be a resource template.');
        }

        return $attributes[0]->newInstance();
    }
}
