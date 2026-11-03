<?php

namespace Fabricate\Schema\V20260728\Concerns;

enum CacheScope: string
{
    case PUBLIC = 'public';
    case PRIVATE = 'private';
}
