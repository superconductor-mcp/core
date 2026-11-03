<?php

namespace Fabricate\Schema\V20251125;

use Fabricate\Schema\V20251125\Concerns\TaskStatus;
use Voyager\Contracts\NutsAndBolts\Arrayable;

readonly class Task implements Arrayable
{
    public function __construct(
        public string $taskId,
        public TaskStatus $status,
        public string $createdAt,
        public string $lastUpdatedAt,
        public ?int $ttl,
        public ?string $statusMessage = null,
        public ?int $pollInterval = null,
    ) {}

    public function toArray(): array
    {
        $results = [
            'taskId' => $this->taskId,
            'status' => $this->status->value,
            'createdAt' => $this->createdAt,
            'lastUpdatedAt' => $this->lastUpdatedAt,
            'ttl' => $this->ttl,
        ];

        if(isset($this->statusMessage)) $results['statusMessage'] = $this->statusMessage;
        if(isset($this->pollInterval)) $results['pollInterval'] = $this->pollInterval;

        return $results;
    }
}
