<?php

namespace Fabricate\Schema\V20251125\Messaging\Tasks;

use Fabricate\Contracts\Schema\Messaging\Tasks\ListTasksResult as ListTasksResultContract;
use Fabricate\Contracts\Schema\Cursor;
use Fabricate\Contracts\Schema\Messaging\ClientResult;
use Fabricate\Contracts\Schema\Messaging\ServerResult;
use Fabricate\Schema\V20251125\Messaging\Pagination\PaginatedResult;
use Fabricate\Schema\V20251125\Task;

final readonly class ListTasksResult extends PaginatedResult implements ListTasksResultContract, ClientResult, ServerResult
{
    /**
     * @param Task[] $tasks
     */
    public function __construct(
        public array $tasks,
        ?Cursor $nextCursor = null,
        ?array $_meta = null
    ) {
        parent::__construct($nextCursor, $_meta);
    }

    public function toArray(): array
    {
        $results = parent::toArray();

        $results['tasks'] = array_map(fn(Task $task) => $task->toArray(), $this->tasks);

        return $results;
    }
}
