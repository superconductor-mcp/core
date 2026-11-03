<?php

namespace Fabricate\Contracts\Mechanism;

use Fabricate\Mechanism\CompletionResult;

interface Prompt
{
    /** The name the prompt is listed and fetched by. */
    public static function name(): string;

    public static function description(): ?string;

    /**
     * The prompt's arguments in order, and whether a request must supply each. Every argument is a string on the wire.
     *
     * @return list<array{name: string, required: bool}>
     */
    public static function arguments(): array;

    /**
     * Suggestions for an argument from what has been typed so far.
     *
     * @param array<string, string> $context Arguments already filled in, by name.
     */
    public static function complete(string $argument, string $value, array $context = []): CompletionResult;

    /** A human-readable name for display, from 2025-06-18. */
    public static function title(): ?string;

    /**
     * Icons for display, from 2025-11-25.
     *
     * @return list<\Fabricate\Contracts\Mechanism\Attributes\Icon>
     */
    public static function icons(): array;
}
