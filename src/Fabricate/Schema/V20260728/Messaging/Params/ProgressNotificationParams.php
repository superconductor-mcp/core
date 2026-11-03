<?php

namespace Fabricate\Schema\V20260728\Messaging\Params;

use Fabricate\Contracts\Schema\ProgressToken;
use Fabricate\Schema\V20260728\Messaging\MetaObjects\NotificationMetaObject;

final readonly class ProgressNotificationParams extends NotificationParams
{
    public function __construct(
        public ProgressToken $progressToken,
        public int|float $progress,
        public int|float|null $total = null,
        public ?string $message = null,
        ?NotificationMetaObject $_meta = null,
    ) {
        parent::__construct($_meta);
    }

    public function toArray(): array
    {
        $results = parent::toArray();

        $results['progressToken'] = $this->progressToken->token;
        $results['progress'] = $this->progress;
        if(isset($this->total)) $results['total'] = $this->total;
        if(isset($this->message)) $results['message'] = $this->message;

        return $results;
    }
}
