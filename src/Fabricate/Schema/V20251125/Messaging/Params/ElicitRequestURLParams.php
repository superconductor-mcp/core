<?php

namespace Fabricate\Schema\V20251125\Messaging\Params;

use Fabricate\Contracts\Schema\ProgressToken;
use Fabricate\Schema\V20251125\Concerns\ElicitRequestParams;
use Fabricate\Schema\V20251125\TaskMetadata;

final readonly class ElicitRequestURLParams extends TaskAugmentedRequestParams implements ElicitRequestParams
{
    public string $mode;

    public function __construct(
        public string $message,
        public string $elicitationId,
        public string $url,
        ?TaskMetadata $task = null,
        ?array $_meta = null,
        ?ProgressToken $progress_token = null
    ) {
        $this->mode = 'url';

        parent::__construct($task, $_meta, $progress_token);
    }

    public function toArray(): array
    {
        $results = parent::toArray();

        $results['mode'] = $this->mode;
        $results['message'] = $this->message;
        $results['elicitationId'] = $this->elicitationId;
        $results['url'] = $this->url;

        return $results;
    }
}
