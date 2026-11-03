<?php

namespace Fabricate\Client;

use Fabricate\Mechanism\DiscoverEntities;

class DiscoverClients extends DiscoverEntities
{
    protected static string $configuration = ClientConfiguration::class;

    /** @return list<class-string<ClientConfiguration>> */
    public static function within(string $path, string $namespace): array
    {
        return static::_within($path, $namespace);
    }
}
