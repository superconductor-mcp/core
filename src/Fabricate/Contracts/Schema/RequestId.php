<?php

namespace Fabricate\Contracts\Schema;

final readonly class RequestId
{
    public function __construct(
        public string|int $id
    ) {}
}
