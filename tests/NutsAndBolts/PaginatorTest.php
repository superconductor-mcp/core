<?php

use Fabricate\NutsAndBolts\Paginator;

it('walks a list page by page through opaque cursors', function () {
    [$page, $cursor] = Paginator::page(['a', 'b', 'c'], null, 2);
    expect($page)->toBe(['a', 'b'])->and($cursor)->toBeString();

    [$page, $cursor] = Paginator::page(['a', 'b', 'c'], $cursor, 2);
    expect($page)->toBe(['c'])->and($cursor)->toBeNull();
});

it('refuses a cursor it did not make', function () {
    expect(fn () => Paginator::page(['a'], 'garbage', 1))->toThrow(InvalidArgumentException::class);
});
