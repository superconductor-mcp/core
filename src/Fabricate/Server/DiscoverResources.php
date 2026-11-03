<?php

namespace Fabricate\Server;

use Fabricate\Mechanism\DiscoverEntities;
use Fabricate\Mechanism\Resource;

class DiscoverResources extends DiscoverEntities
{
    protected static string $configuration = Resource::class;

    /** @return list<class-string<Resource>> */
    public static function within(string $path, string $namespace): array
    {
        return static::_within($path, $namespace);
    }
}
