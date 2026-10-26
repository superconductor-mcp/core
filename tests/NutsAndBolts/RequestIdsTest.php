<?php

use Fabricate\NutsAndBolts\RequestIds;

it('accepts an id once and refuses it the second time', function () {
    $ids = new RequestIds();

    expect($ids->receive(1))->toBeTrue()
        ->and($ids->receive(1))->toBeFalse()
        ->and($ids->receive('1'))->toBeTrue()   // a string "1" is not the integer 1
        ->and($ids->receive('1'))->toBeFalse();
});

it('hands out the next integer after the greatest one seen, starting at 0', function () {
    $ids = new RequestIds();

    expect($ids->next())->toBe(0)
        ->and($ids->next())->toBe(1);

    $ids->receive(10);
    expect($ids->next())->toBe(11);

    $ids->receive('abc');   // strings never move the counter
    expect($ids->next())->toBe(12);
});

it('starts over when told to forget', function () {
    $ids = new RequestIds();
    $ids->next();
    $ids->receive(5);
    $ids->forget();

    expect($ids->next())->toBe(0)->and($ids->receive(5))->toBeTrue();
});
