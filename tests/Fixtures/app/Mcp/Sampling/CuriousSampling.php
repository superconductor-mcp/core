<?php

namespace App\Mcp\Sampling;

use Fabricate\Client\Sampling;
use Fabricate\Contracts\Schema\Concerns\Role;
use Fabricate\Contracts\Schema\Messaging\Request;
use Fabricate\Contracts\Schema\Messaging\Sampling\CreateMessageResult;
use Fabricate\Schema\V20241105;
use Fabricate\Schema\V20250326;
use Fabricate\Schema\V20250618;
use Fabricate\Schema\V20251125;
use Fabricate\Schema\V20260728;

/**
 * Stands in for a model: answers the last user message with a question about it. A real client connects its model
 * here, through whatever API or bridge it uses.
 */
class CuriousSampling extends Sampling
{
    public function handle(Request $request, string $protocol_version): CreateMessageResult
    {
        $messages = $request->toArray()['params']['messages'] ?? [];
        $content = end($messages)['content'] ?? [];
        // From 2025-11-25 a message's content may be a list of blocks.
        $said = array_is_list($content) ? implode(' ', array_column($content, 'text')) : ($content['text'] ?? '');
        $reply = "Why \"{$said}\"?";

        return match($protocol_version) {
            '2024-11-05' => new V20241105\Messaging\Sampling\CreateMessageResult('curious-0', Role::ASSISTANT, new V20241105\Concerns\TextContent($reply), 'endTurn'),
            '2025-03-26' => new V20250326\Messaging\Sampling\CreateMessageResult('curious-0', Role::ASSISTANT, new V20250326\Concerns\TextContent($reply), 'endTurn'),
            '2025-06-18' => new V20250618\Messaging\Sampling\CreateMessageResult('curious-0', Role::ASSISTANT, new V20250618\Concerns\TextContent($reply), 'endTurn'),
            '2025-11-25' => new V20251125\Messaging\Sampling\CreateMessageResult('curious-0', Role::ASSISTANT, new V20251125\Concerns\TextContent($reply), 'endTurn'),
            '2026-07-28' => new V20260728\Messaging\Sampling\CreateMessageResult('curious-0', Role::ASSISTANT, new V20260728\Concerns\TextContent($reply), 'endTurn'),
        };
    }
}
