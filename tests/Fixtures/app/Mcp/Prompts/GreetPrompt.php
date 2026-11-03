<?php

namespace App\Mcp\Prompts;

use Fabricate\Contracts\Mechanism\Attributes\PromptCall;
use Fabricate\Mechanism\CompletionResult;
use Fabricate\Mechanism\Prompt;

#[PromptCall('greet')]
class GreetPrompt extends Prompt
{
    private const array TONES = ['friendly', 'formal', 'pirate'];

    public function __construct(
        public readonly string $name,
        public readonly string $tone = 'friendly',
    ) {}

    public static function description(): ?string
    {
        return 'Ask for a greeting in the given tone.';
    }

    public static function complete(string $argument, string $value, array $context = []): CompletionResult
    {
        return new CompletionResult($argument === 'tone'
            ? array_values(array_filter(self::TONES, fn (string $tone) => str_starts_with($tone, $value)))
            : []);
    }

    public function handle(): string
    {
        return "Write a {$this->tone} greeting for {$this->name}.";
    }
}
