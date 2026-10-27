<?php

namespace Fabricate\NutsAndBolts;

use InvalidArgumentException;

// the `_meta` map MCP reserves on messages and definitions; key names follow the spec's format, checked where they are set
final class Meta
{
    // optional prefix: dot-separated labels then a slash; name: alphanumeric at both ends, - _ . inside
    private const string KEY = '/^(?:(?:[A-Za-z](?:[A-Za-z0-9-]*[A-Za-z0-9])?\.)*[A-Za-z](?:[A-Za-z0-9-]*[A-Za-z0-9])?\/)?(?:[A-Za-z0-9](?:[A-Za-z0-9._-]*[A-Za-z0-9])?)?$/';

    /** @return array<string, mixed> the map, unchanged, once every key is a valid name */
    public static function check(array $meta): array
    {
        foreach (array_keys($meta) as $key) {
            if (! is_string($key) || ! preg_match(self::KEY, $key)) {
                throw new InvalidArgumentException("[{$key}] is not a valid _meta key.");
            }
        }

        return $meta;
    }

    /** @param array<string, mixed> $fields a toArray() result; `_meta` joins it only when there is some */
    public static function attach(array $fields, array $meta): array
    {
        if ($meta !== []) {
            $fields['_meta'] = (object) $meta;
        }

        return $fields;
    }
}
