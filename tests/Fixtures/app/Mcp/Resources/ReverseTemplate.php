<?php

namespace App\Mcp\Resources;

use Fabricate\Contracts\Mechanism\Attributes\ResourceTemplateCall;
use Fabricate\Mechanism\CompletionResult;
use Fabricate\Mechanism\ResourceTemplate;

#[ResourceTemplateCall('echo://reverse/{text}', 'reverse', 'Any text, read back to front.', 'text/plain')]
class ReverseTemplate extends ResourceTemplate
{
    private const array WORDS = ['saturn', 'superconductor', 'stressed', 'racecar'];

    public static function complete(string $argument, string $value, array $context = []): CompletionResult
    {
        return new CompletionResult(array_values(array_filter(self::WORDS, fn(string $word) => str_starts_with($word, $value))));
    }

    public function read(string $uri, string $text): string
    {
        return strrev($text);
    }
}
