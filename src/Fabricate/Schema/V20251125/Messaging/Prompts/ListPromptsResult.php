<?php

namespace Fabricate\Schema\V20251125\Messaging\Prompts;

use Fabricate\Contracts\Schema\Cursor;
use Fabricate\Contracts\Schema\Messaging\Prompts\ListPromptsResult as ListPromptsContract;
use Fabricate\Contracts\Schema\Messaging\ServerResult;
use Fabricate\Schema\V20251125\Capabilities\Prompts\PromptDefinition;
use Fabricate\Schema\V20251125\Messaging\Pagination\PaginatedResult;

final readonly class ListPromptsResult extends PaginatedResult implements ListPromptsContract, ServerResult
{
    /**
     * @param PromptDefinition[] $prompts
     * @param Cursor|null $nextCursor
     * @param array|null $_meta
     */
    public function __construct(
        public array $prompts,
        ?Cursor $nextCursor = null,
        ?array $_meta = null
    ) {
        parent::__construct($nextCursor,$_meta);
    }

    public function toArray(): array
    {
        $results = parent::toArray();

        $results['prompts'] = array_map(fn(PromptDefinition $prompt) => $prompt->toArray(), $this->prompts);

        return $results;
    }
}
