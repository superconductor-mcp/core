<?php

namespace Fabricate\NutsAndBolts;

use InvalidArgumentException;

// cuts a list into pages for the paginated list methods; the cursor is an opaque, encoded offset
final class Paginator
{
    /**
     * @param list<mixed> $items
     * @return array{0: list<mixed>, 1: ?string} the page and the cursor for the next one, or null on the last page
     *
     * @throws InvalidArgumentException when the cursor is not one this paginator issued
     */
    public static function page(array $items, ?string $cursor, int $page_size): array
    {
        $offset = is_null($cursor) ? 0 : self::offset($cursor);
        $page_size = max(1, $page_size);
        $page = array_slice(array_values($items), $offset, $page_size);
        $next = $offset + $page_size;

        return [$page, $next < count($items) ? self::cursor($next) : null];
    }

    private static function cursor(int $offset): string
    {
        return rtrim(strtr(base64_encode(json_encode(['offset' => $offset])), '+/', '-_'), '=');
    }

    private static function offset(string $cursor): int
    {
        $decoded = json_decode((string) base64_decode(strtr($cursor, '-_', '+/'), true), true);
        $offset = is_array($decoded) ? ($decoded['offset'] ?? null) : null;

        if (! is_int($offset) || $offset < 0) {
            throw new InvalidArgumentException('Invalid cursor.');
        }

        return $offset;
    }
}
