<?php

namespace Fabricate\Schema\V20260728\Concerns;

enum MCPErrorCode: int
{
    case HEADER_MISMATCH = -32020;
    case MISSING_REQUIRED_CLIENT_CAPABILITY = -32021;
    case UNSUPPORTED_PROTOCOL_VERSION = -32022;
}
