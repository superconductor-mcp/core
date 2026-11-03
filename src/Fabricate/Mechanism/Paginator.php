<?php

namespace Fabricate\Mechanism;

use Fabricate\Contracts\Core\SuperconductorMCPException;
use Fabricate\Contracts\Schema\Cursor;
use Fabricate\Contracts\Schema\RPCErrorCode;

/**
 * Splits a list into pages behind opaque cursors that encode the next page's offset.
 */
final class Paginator
{
    /**
     * @template T
     * @param list<T> $items
     * @return array{0: list<T>, 1: ?Cursor} The page, and the cursor for the next one or null on the last page.
     */
    public static function page(array $items, ?Cursor $cursor, int $page_size): array
    {
        $offset = is_null($cursor) ? 0 : self::offset($cursor);
        $page_size = max(1, $page_size);
        $next = $offset + $page_size;

        return [array_slice($items, $offset, $page_size), $next < count($items) ? self::cursor($next) : null];
    }

    private static function cursor(int $offset): Cursor
    {
        return new Cursor(rtrim(strtr(base64_encode(json_encode(['offset' => $offset])), '+/', '-_'), '='));
    }

    private static function offset(Cursor $cursor): int
    {
        $decoded = json_decode((string) base64_decode(strtr($cursor->pointer, '-_', '+/'), true), true);
        $offset = is_array($decoded) ? ($decoded['offset'] ?? null) : null;

        if(! is_int($offset) || $offset < 0) {
            throw new SuperconductorMCPException('Invalid cursor.', RPCErrorCode::INVALID_PARAMS->value);
        }

        return $offset;
    }
}
