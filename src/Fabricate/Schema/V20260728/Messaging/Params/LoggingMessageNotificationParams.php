<?php

namespace Fabricate\Schema\V20260728\Messaging\Params;

use Fabricate\Contracts\Schema\LoggingLevel;
use Fabricate\Schema\V20260728\Messaging\MetaObjects\NotificationMetaObject;

final readonly class LoggingMessageNotificationParams extends NotificationParams
{
    public function __construct(
        public LoggingLevel $level,
        public mixed $data,
        public ?string $logger = null,
        ?NotificationMetaObject $_meta = null,
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
