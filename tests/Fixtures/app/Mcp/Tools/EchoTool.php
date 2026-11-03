<?php

namespace App\Mcp\Tools;

use Fabricate\Mechanism\Tool;
use Fabricate\Contracts\Mechanism\Attributes\ToolCall;

#[ToolCall('echo')]
class EchoTool extends Tool
{
    public function __construct(
        public readonly string $message,
    ) {}

    public static function description(): string
    {
        return 'Send back the message as given.';
    }

    public function handle(): string
    {
        return $this->message;
    }
}
