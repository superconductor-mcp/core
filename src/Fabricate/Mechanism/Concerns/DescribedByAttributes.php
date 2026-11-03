<?php

namespace Fabricate\Mechanism\Concerns;

use Fabricate\Contracts\Mechanism\Attributes\Icon;
use ReflectionClass;

/**
 * Reads the class's #[Icon] attributes, and the one instance of any other attribute it may carry.
 */
trait DescribedByAttributes
{
    /**
     * @return list<Icon>
     */
    public static function icons(): array
    {
        return array_map(fn($attribute) => $attribute->newInstance(), new ReflectionClass(static::class)->getAttributes(Icon::class));
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return T|null
     */
    protected static function classAttribute(string $class): ?object
    {
        return (new ReflectionClass(static::class)->getAttributes($class)[0] ?? null)?->newInstance();
    }
}
