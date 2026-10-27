<?php

namespace Fabricate\NutsAndBolts;

// which capability a method needs; 2025-06-18 makes "only use what was negotiated" a MUST on both sides
final class Capabilities
{
    /** @return list<string>|null the capability path the method needs, or null when it needs none */
    public static function requiredBy(string $method, ?string $protocol_version): ?array
    {
        return match ($method) {
            'tools/list', 'tools/call' => ['tools'],
            'resources/list', 'resources/read', 'resources/templates/list' => ['resources'],
            'resources/subscribe', 'resources/unsubscribe' => ['resources', 'subscribe'],
            'prompts/list', 'prompts/get' => ['prompts'],
            'logging/setLevel' => ['logging'],
            // 2024-11-05 had completion/complete without a capability to declare it
            'completion/complete' => ($protocol_version ?? '2024-11-05') >= '2025-03-26' ? ['completions'] : null,
            'elicitation/create' => ['elicitation'],
            'sampling/createMessage' => ['sampling'],
            'roots/list' => ['roots'],
            default => null,
        };
    }

    // a path is declared when every step exists; the last step may be a flag that must be true
    public static function declares(array $capabilities, array $path): bool
    {
        $node = $capabilities;

        foreach ($path as $i => $step) {
            if (! is_array($node) || ! array_key_exists($step, $node)) {
                return false;
            }

            $node = $node[$step];

            if ($i === array_key_last($path) && is_bool($node)) {
                return $node;
            }
        }

        return true;
    }
}
