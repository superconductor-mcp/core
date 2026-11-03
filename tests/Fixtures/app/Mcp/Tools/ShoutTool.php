<?php

namespace App\Mcp\Tools;

use Fabricate\Contracts\Mechanism\Attributes\ToolCall;
use Fabricate\Mechanism\Tool;

#[ToolCall('shout')]
class ShoutTool extends Tool
{
    public function __construct(
        public readonly string $message,
    ) {}

    public static function description(): string
    {
        return 'Send back the message in upper case.';
    }

    public function handle(): string
    {
        return mb_strtoupper($this->message);
    }
}
