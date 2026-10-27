<?php

declare(strict_types=1);

namespace Fabricate\NutsAndBolts\Schema;

use TypeError;
use InvalidArgumentException;
use Voyager\Contracts\NutsAndBolts\Arrayable;
use Fabricate\Contracts\Mechanism\SchemaPrimitive;

// reads content the other side sent into the same primitives this side sends
final class Content
{
    /**
     * One content item: text, image, audio, an embedded resource or a resource link.
     *
     * @throws InvalidArgumentException naming what is wrong
     */
    public static function item(mixed $item): SchemaPrimitive&Arrayable
    {
        if (! is_array($item)) {
            throw new InvalidArgumentException('a content item must be an object.');
        }

        $type = $item['type'] ?? null;

        return self::guard("{$type} content", fn (): SchemaPrimitive&Arrayable => match ($type) {
            'text' => new TextProperty($item['text'] ?? null, self::annotations($item), self::meta($item)),
            'image' => new ImageProperty($item['data'] ?? null, $item['mimeType'] ?? null, self::annotations($item), self::meta($item)),
            'audio' => new AudioProperty($item['data'] ?? null, $item['mimeType'] ?? null, self::annotations($item), self::meta($item)),
            'resource' => self::embedded($item),
            'resource_link' => new ResourceLinkProperty($item['uri'] ?? null, $item['name'] ?? null, $item['title'] ?? null, $item['description'] ?? null, $item['mimeType'] ?? null, self::annotations($item), self::meta($item)),
            default => throw new InvalidArgumentException('content type must be text, image, audio, resource or resource_link.'),
        });
    }

    // the contents of one resource, as resources/read returns them and an embedded resource carries them
    public static function resource(mixed $contents): ResourceContents
    {
        if (! is_array($contents)) {
            throw new InvalidArgumentException('resource contents must be an object.');
        }

        return self::guard('resource contents', fn (): ResourceContents => new ResourceContents(
            $contents['uri'] ?? null,
            $contents['mimeType'] ?? null,
            $contents['text'] ?? null,
            $contents['blob'] ?? null,
            self::meta($contents),
        ));
    }

    private static function embedded(array $item): EmbeddedResourceProperty
    {
        $contents = self::resource($item['resource'] ?? null);

        return new EmbeddedResourceProperty($contents->uri, $contents->mime_type, $contents->text, $contents->blob, self::annotations($item), self::meta($item));
    }

    private static function annotations(array $item): ?Annotations
    {
        $annotations = $item['annotations'] ?? null;

        if (is_null($annotations)) {
            return null;
        }

        if (! is_array($annotations)) {
            throw new InvalidArgumentException('annotations must be an object.');
        }

        $priority = $annotations['priority'] ?? null;

        // JSON has one number type; a priority of 1 arrives as an int
        return new Annotations($annotations['audience'] ?? null, is_int($priority) ? (float) $priority : $priority, $annotations['lastModified'] ?? null);
    }

    private static function meta(array $item): array
    {
        $meta = $item['_meta'] ?? [];

        if (! is_array($meta)) {
            throw new InvalidArgumentException('_meta must be an object.');
        }

        return $meta;
    }

    /**
     * @template T
     * @param \Closure(): T $build
     * @return T
     */
    private static function guard(string $what, \Closure $build): mixed
    {
        try {
            return $build();
        } catch (TypeError) {
            throw new InvalidArgumentException("{$what} is missing a field or has one of the wrong type.");
        }
    }
}
