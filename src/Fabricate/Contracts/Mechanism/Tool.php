<?php

namespace Fabricate\Contracts\Mechanism;

interface Tool
{
    /** The name the tool is listed and called by. */
    public static function name(): string;

    public static function description(): ?string;

    /**
     * The tool's arguments as JSON Schema: each property's schema by name, and the names a call must supply.
     *
     * @return array{properties: array<string, array<string, mixed>>, required: list<string>}
     */
    public static function inputSchema(): array;

    /** Whether a 2025-11-25 client may run the tool as a task: forbidden, optional or required. */
    public static function taskSupport(): string;

    /** A human-readable name for display, from 2025-06-18. */
    public static function title(): ?string;

    /**
     * Icons for display, from 2025-11-25.
     *
     * @return list<\Fabricate\Contracts\Mechanism\Attributes\Icon>
     */
    public static function icons(): array;

    /**
     * The arguments a 2026-07-28 Streamable HTTP call mirrors into Mcp-Param-{header} headers: each header by argument.
     *
     * @return array<string, string>
     */
    public static function mirroredHeaders(): array;
}
