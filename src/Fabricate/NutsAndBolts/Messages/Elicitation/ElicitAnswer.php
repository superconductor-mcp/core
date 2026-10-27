<?php

namespace Fabricate\NutsAndBolts\Messages\Elicitation;

// the user's answer to an elicitation; content only when they accepted
final readonly class ElicitAnswer
{
    /** @param array<string, string|int|float|bool>|null $content */
    public function __construct(
        public ElicitAction $action,
        public ?array $content = null,
    ) {}

    /** @param array<string, string|int|float|bool> $content */
    public static function accept(array $content): self
    {
        return new self(ElicitAction::Accept, $content);
    }

    public static function decline(): self
    {
        return new self(ElicitAction::Decline);
    }

    public static function cancel(): self
    {
        return new self(ElicitAction::Cancel);
    }

    public function accepted(): bool
    {
        return $this->action === ElicitAction::Accept;
    }
}
