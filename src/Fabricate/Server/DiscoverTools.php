<?php

namespace Fabricate\Server;

use Fabricate\Mechanism\DiscoverEntities;
use Fabricate\Mechanism\Tool;

class DiscoverTools extends DiscoverEntities
{
    protected static string $configuration = Tool::class;

    /** @return list<class-string<Tool>> */
    public static function within(string $path, string $namespace): array
    {
        return static::_within($path, $namespace);
    }
}
