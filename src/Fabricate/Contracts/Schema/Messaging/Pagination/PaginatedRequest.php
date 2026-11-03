<?php

namespace Fabricate\Contracts\Schema\Messaging\Pagination;

use Fabricate\Contracts\Schema\Cursor;
use Fabricate\Contracts\Schema\Messaging\Request;

interface PaginatedRequest extends Request
{
    public function cursor(): ?Cursor;
}
