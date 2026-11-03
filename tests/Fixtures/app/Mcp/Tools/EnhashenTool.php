<?php

namespace App\Mcp\Tools;

use Fabricate\Contracts\Mechanism\Attributes\ToolCall;
use Fabricate\Mechanism\Tool;

#[ToolCall('enhashen')]
class EnhashenTool extends Tool
{
    public function __construct(
        public readonly string $message,
    ) {}

    public static function description(): string
    {
        return 'Send back a hash of the message.';
    }

    public function handle(): string
    {
        return hash('sha256', $this->message);
    }
}
