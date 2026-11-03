<?php

namespace Fabricate\Schema\V20251125\Messaging\Resources;

use Fabricate\Contracts\Schema\Cursor;
use Fabricate\Contracts\Schema\Messaging\Resources\ListResourceTemplatesResult as ListTemplatesContract;
use Fabricate\Contracts\Schema\Messaging\ServerResult;
use Fabricate\Schema\V20251125\Capabilities\Resources\ResourceTemplateDefinition;
use Fabricate\Schema\V20251125\Messaging\Pagination\PaginatedResult;

final readonly class ListResourceTemplatesResult extends PaginatedResult implements ListTemplatesContract, ServerResult
{
    /**
     * @param ResourceTemplateDefinition[] $resourceTemplates
     */
    public function __construct(
        public array $resourceTemplates,
        ?Cursor $nextCursor = null,
        ?array $_meta = null
    ) {
        parent::__construct($nextCursor, $_meta);
    }

    public function toArray(): array
    {
        $results = parent::toArray();

        $results['resourceTemplates'] = array_map(fn(ResourceTemplateDefinition $template) => $template->toArray(), $this->resourceTemplates);

        return $results;
    }
}
