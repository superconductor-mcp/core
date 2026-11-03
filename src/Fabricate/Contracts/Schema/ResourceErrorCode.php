<?php

namespace Fabricate\Contracts\Schema;

/**
 * The resources spec's own code for a URI nothing reads, 2024-11-05 through 2025-11-25. 2026-07-28 moved it to -32602.
 */
enum ResourceErrorCode: int
{
    case RESOURCE_NOT_FOUND = -32002;
}
