<?php

namespace Fabricate\Server;

use Fabricate\Mechanism\DiscoverEntities;

class DiscoverServers extends DiscoverEntities
{
    protected static string $configuration = ServerConfiguration::class;

    /** @return list<class-string<ServerConfiguration>> */
    public static function within(string $path, string $namespace): array
    {
        return static::_within($path, $namespace);
    }
}
