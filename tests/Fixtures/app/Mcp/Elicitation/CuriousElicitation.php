<?php

namespace App\Mcp\Elicitation;

use Fabricate\Client\Elicitation;
use Fabricate\Contracts\Schema\ElicitAction;
use Fabricate\Contracts\Schema\Messaging\Elicitation\ElicitResult;
use Fabricate\Contracts\Schema\Messaging\Request;
use Fabricate\Schema\V20250618;
use Fabricate\Schema\V20251125;
use Fabricate\Schema\V20260728;

/**
 * Stands in for a user with no time to type: accepts a form when every field it requires has a default, answering
 * with the defaults, and declines otherwise. A real client shows the form, naming the server that asks.
 */
class CuriousElicitation extends Elicitation
{
    public function handle(Request $request, string $protocol_version): ElicitResult
    {
        $schema = $request->toArray()['params']['requestedSchema'] ?? [];
        $properties = (array) ($schema['properties'] ?? []);
        $defaults = array_filter(array_map(fn(array $property) => $property['default'] ?? null, $properties), fn($value) => ! is_null($value));
        $complete = array_diff($schema['required'] ?? [], array_keys($defaults)) === [];

        [$action, $content] = $complete ? [ElicitAction::ACCEPT, $defaults] : [ElicitAction::DECLINE, null];

        return match($protocol_version) {
            '2025-06-18' => new V20250618\Messaging\Elicitation\ElicitResult($action, $content),
            '2025-11-25' => new V20251125\Messaging\Elicitation\ElicitResult($action, $content),
            '2026-07-28' => new V20260728\Messaging\Elicitation\ElicitResult($action, $content),
        };
    }
}
