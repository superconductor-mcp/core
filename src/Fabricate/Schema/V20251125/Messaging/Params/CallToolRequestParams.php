<?php

namespace Fabricate\Schema\V20251125\Messaging\Params;

use Fabricate\Contracts\Schema\ProgressToken;
use Fabricate\Schema\V20251125\TaskMetadata;

final readonly class CallToolRequestParams extends TaskAugmentedRequestParams
{
    /**
     * @param array<string,mixed>|null $arguments
     */
    public function __construct(
        public string $name,
        public ?array $arguments = null,
        ?TaskMetadata $task = null,
        ?array $_meta = null,
        ?ProgressToken $progress_token = null
    ) {
        parent::__construct($task, $_meta, $progress_token);
    }

    public function toArray(): array
    {
        $results = parent::toArray();

        $results['name'] = $this->name;
        if(isset($this->arguments)) $results['arguments'] = $this->arguments ?: (object) [];

        return $results;
    }
}
