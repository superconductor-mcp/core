<?php

namespace Fabricate\NutsAndBolts\Schema;

class IntegerProperty extends NumberProperty
{
    protected const string TYPE = 'integer';

    // JSON has one number type: 3.0 is an integer, 2.5 is not
    public function problem(mixed $value): ?string
    {
        return is_int($value) || (is_float($value) && floor($value) === $value) ? parent::problem($value) : 'must be an integer.';
    }
}
