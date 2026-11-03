<?php

namespace Fabricate\Server;

use Fabricate\Mechanism\DiscoverEntities;
use Fabricate\Mechanism\Prompt;

class DiscoverPrompts extends DiscoverEntities
{
    protected static string $configuration = Prompt::class;

    /** @return list<class-string<Prompt>> */
    public static function within(string $path, string $namespace): array
    {
        return static::_within($path, $namespace);
    }
}
