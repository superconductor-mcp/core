<?php

namespace Fabricate\Contracts\Mechanism;

use Fabricate\Mechanism\CompletionResult;

interface ResourceTemplate
{
    /** The RFC 6570 URI template the resources it reads fit. */
    public static function uriTemplate(): string;

    public static function name(): string;

    public static function description(): ?string;

    public static function mimeType(): ?string;

    /**
     * The template's variables, in order: the arguments completion/complete can ask about.
     *
     * @return list<string>
     */
    public static function variables(): array;

    /**
     * The template's variables filled from a URI that fits it, by name; null when the URI doesn't fit.
     *
     * @return array<string, string>|null
     */
    public static function match(string $uri): ?array;

    /**
     * Suggestions for a variable from what has been typed so far.
     *
     * @param array<string, string> $context Variables already filled in, by name.
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
