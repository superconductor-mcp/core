<?php

namespace Fabricate\Contracts\Schema;

final readonly class Cursor
{
    public function __construct(
        public string $pointer
    ) {}
}
