<?php

namespace Fabricate\Contracts\Schema;

/**
 * The error the client sampling pages of 2024-11-05 through 2025-11-25 give for a sampling request the user rejected.
 * schema.ts names no code for it.
 */
enum SamplingErrorCode: int
{
    case USER_REJECTED = -1;
}
