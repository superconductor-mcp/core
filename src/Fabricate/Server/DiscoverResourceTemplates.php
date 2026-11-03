<?php

namespace Fabricate\Server;

use Fabricate\Mechanism\DiscoverEntities;
use Fabricate\Mechanism\ResourceTemplate;

class DiscoverResourceTemplates extends DiscoverEntities
{
    protected static string $configuration = ResourceTemplate::class;

    /** @return list<class-string<ResourceTemplate>> */
    public static function within(string $path, string $namespace): array
    {
        return static::_within($path, $namespace);
    }
}
