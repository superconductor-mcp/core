<?php

namespace Fabricate\Schema\V20250618\Messaging\Tools;

use Fabricate\Contracts\Schema\Cursor;
use Fabricate\Contracts\Schema\Messaging\ServerResult;
use Fabricate\Contracts\Schema\Messaging\Tools\ListToolsResult as ListToolsContract;
use Fabricate\Schema\V20250618\Capabilities\Tools\ToolDefinition;
use Fabricate\Schema\V20250618\Messaging\Pagination\PaginatedResult;

final readonly class ListToolsResult extends PaginatedResult implements ListToolsContract, ServerResult
{
    /**
     * @param ToolDefinition[] $tools
     * @param Cursor|null $nextCursor
     * @param array|null $_meta
     */
    public function __construct(
        public array $tools,
        ?Cursor $nextCursor = null,
        ?array $_meta = null
    ) {
        parent::__construct($nextCursor, $_meta);
    }

    public function toArray(): array
    {
        $results = parent::toArray();

        $results['tools'] = array_map(fn(ToolDefinition $tool) => $tool->toArray(), $this->tools);

        return $results;
    }
}
