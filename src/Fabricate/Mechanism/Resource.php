<?php

namespace Fabricate\Mechanism;

use Fabricate\Contracts\Mechanism\Attributes\ResourceAnnotations;
use Fabricate\Contracts\Mechanism\Attributes\ResourceCall;
use Fabricate\Mechanism\Concerns\DescribedByAttributes;
use Fabricate\Contracts\Mechanism\MCPMechanismException;
use Fabricate\Contracts\Mechanism\Resource as ResourceContract;
use Fabricate\Mechanism\Concerns\RunsAsync;
use Fabricate\Mechanism\Concerns\SignalsChanges;
use Fabricate\Mechanism\Signals\ListChanged;
use Fabricate\Mechanism\Signals\ResourceUpdated;
use ReflectionClass;
use Voyager\NutsAndBolts\Str;

/**
 * A resource at one URI. A concrete carries #[ResourceCall] and a read() method that returns its contents: a string,
 * the version's own contents objects, or a list of them. read() is called through the container, so it can ask for
 * the server configuration, $uri and $protocol_version.
 */
abstract class Resource implements ResourceContract
{
    use RunsAsync, SignalsChanges, DescribedByAttributes;

    public static function uri(): string
    {
        return static::resourceCall()->uri;
    }

    public static function name(): string
    {
        return static::resourceCall()->name ?? Str::kebab(class_basename(static::class));
    }

    public static function description(): ?string
    {
        return static::resourceCall()->description;
    }

    public static function mimeType(): ?string
    {
        return static::resourceCall()->mimeType;
    }

    public static function title(): ?string
    {
        return static::resourceCall()->title;
    }

    /** The resource's size in bytes, if known. */
    public static function size(): ?int
    {
        return static::resourceCall()->size;
    }

    public static function annotations(): ?ResourceAnnotations
    {
        return static::classAttribute(ResourceAnnotations::class);
    }

    /**
     * Tells every session subscribed to this resource that its contents changed.
     */
    public static function updated(): void
    {
        signal(new ResourceUpdated(static::uri()));
    }

    protected static function listCapability(): string
    {
        return ListChanged::RESOURCES;
    }

    private static function resourceCall(): ResourceCall
    {
        $attributes = new ReflectionClass(static::class)->getAttributes(ResourceCall::class);

        if($attributes === []) {
            throw new MCPMechanismException(static::class.' needs a #[ResourceCall] attribute to be a resource.');
        }

        return $attributes[0]->newInstance();
    }
}
