<?php

namespace App\Mcp\Resources;

use Fabricate\Contracts\Mechanism\Attributes\ResourceCall;
use Fabricate\Mechanism\Resource;

/**
 * What ponder has thought up this process, newest last. Every finished ponder tells subscribers it changed.
 */
#[ResourceCall('echo://ponderings', 'ponderings', 'Every answer ponder has come back with, newest last.', 'text/plain')]
class PonderingsResource extends Resource
{
    /** @var list<string> */
    private static array $ponderings = [];

    public static function record(string $pondering): void
    {
        self::$ponderings[] = $pondering;
        static::updated();
    }

    public function read(): string
    {
        return self::$ponderings === [] ? 'Nothing pondered yet.' : implode("\n", self::$ponderings);
    }
}
