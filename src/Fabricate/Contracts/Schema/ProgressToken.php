<?php

namespace Fabricate\Contracts\Schema;

final readonly class ProgressToken
{
    public function __construct(
        public string|int $token
    ) {}
}
