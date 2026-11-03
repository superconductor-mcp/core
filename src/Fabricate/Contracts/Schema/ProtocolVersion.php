<?php

namespace Fabricate\Contracts\Schema;

enum ProtocolVersion: string
{
    case V20241105 = '2024-11-05';
    case V20250326 = '2025-03-26';
    case V20250618 = '2025-06-18';
    case V20251125 = '2025-11-25';
    case V20260728 = '2026-07-28';
}
