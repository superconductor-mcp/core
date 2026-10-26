<?php

namespace Fabricate\NutsAndBolts;

use WeakMap;
use Fabricate\NutsAndBolts\Messages\MCPRequest;

// what a request carried in params._meta, kept beside the request rather than inside every request class
final class RequestMeta
{
    private static ?WeakMap $progress_tokens = null;

    public static function rememberProgressToken(MCPRequest $request, string|int $token): void
    {
        self::tokens()[$request] = $token;
    }

    public static function progressToken(MCPRequest $request): string|int|null
    {
        return self::tokens()[$request] ?? null;
    }

    private static function tokens(): WeakMap
    {
        return self::$progress_tokens ??= new WeakMap();
    }
}
