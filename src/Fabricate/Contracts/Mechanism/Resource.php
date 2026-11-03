<?php

namespace Fabricate\Contracts\Mechanism;

interface Resource
{
    /** The URI the resource is listed and read by. */
    public static function uri(): string;

    public static function name(): string;

    public static function description(): ?string;

    public static function mimeType(): ?string;

    /** A human-readable name for display, from 2025-06-18. */
    public static function title(): ?string;

    /**
     * Icons for display, from 2025-11-25.
     *
     * @return list<\Fabricate\Contracts\Mechanism\Attributes\Icon>
     */
    public static function icons(): array;
}
