<?php

namespace Fabricate\Schema\V20251125\Messaging\Params;

use Fabricate\Contracts\Schema\LoggingLevel;

final readonly class LoggingMessageNotificationParams extends NotificationParams
{
    public function __construct(
        public LoggingLevel $level,
        public mixed $data,
        public ?string $logger = null,
        ?array $_meta = null,
    ) {
        parent::__construct($_meta);
    }

    public function toArray(): array
    {
        $results = parent::toArray();

        $results['level'] = $this->level->value;
        $results['data'] = $this->data;
        if(isset($this->logger)) $results['logger'] = $this->logger;

        return $results;
    }
}
