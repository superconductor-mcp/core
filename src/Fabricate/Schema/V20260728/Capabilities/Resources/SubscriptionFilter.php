<?php

namespace Fabricate\Schema\V20260728\Capabilities\Resources;

use Voyager\Contracts\NutsAndBolts\Arrayable;

readonly class SubscriptionFilter implements Arrayable
{
    /**
     * @param string[]|null $resourceSubscriptions
     */
    public function __construct(
        public ?bool $toolsListChanged = null,
        public ?bool $promptsListChanged = null,
        public ?bool $resourcesListChanged = null,
        public ?array $resourceSubscriptions = null,
    ) {}

    public function toArray(): array
    {
        $results = [];

        if(isset($this->toolsListChanged)) $results['toolsListChanged'] = $this->toolsListChanged;
        if(isset($this->promptsListChanged)) $results['promptsListChanged'] = $this->promptsListChanged;
        if(isset($this->resourcesListChanged)) $results['resourcesListChanged'] = $this->resourcesListChanged;
        if(isset($this->resourceSubscriptions)) $results['resourceSubscriptions'] = $this->resourceSubscriptions;

        return $results;
    }
}
