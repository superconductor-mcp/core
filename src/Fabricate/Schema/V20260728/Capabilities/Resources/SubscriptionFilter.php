<?php

namespace Fabricate\Schema\V20260728\Capabilities\Resources;

use Voyager\Contracts\NutsAndBolts\Arrayable;

readonly class SubscriptionFilter implements Arrayable
{
    /**
     * @param string[]|null $resourceSubscriptions
     * @param string[]|null $taskIds From the io.modelcontextprotocol/tasks extension: the tasks to send notifications/tasks for.
     */
    public function __construct(
        public ?bool $toolsListChanged = null,
        public ?bool $promptsListChanged = null,
        public ?bool $resourcesListChanged = null,
        public ?array $resourceSubscriptions = null,
        public ?array $taskIds = null,
    ) {}

    public function toArray(): array
    {
        $results = [];

        if(isset($this->toolsListChanged)) $results['toolsListChanged'] = $this->toolsListChanged;
        if(isset($this->promptsListChanged)) $results['promptsListChanged'] = $this->promptsListChanged;
        if(isset($this->resourcesListChanged)) $results['resourcesListChanged'] = $this->resourcesListChanged;
        if(isset($this->resourceSubscriptions)) $results['resourceSubscriptions'] = $this->resourceSubscriptions;
        if(isset($this->taskIds)) $results['taskIds'] = $this->taskIds;

        return $results;
    }
}
