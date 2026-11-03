<?php

namespace Fabricate\Schema\V20250326\Messaging\Logging;

use Fabricate\Contracts\Schema\LoggingLevel;
use Fabricate\Contracts\Schema\Messaging\Logging\LoggingMessageNotification as LoggingContract;
use Fabricate\Contracts\Schema\Messaging\ServerNotification;
use Fabricate\Schema\V20250326\Messaging\Notification;

final readonly class LoggingMessageNotification extends Notification implements LoggingContract, ServerNotification
{
    public function __construct(
        LoggingLevel $level,
        mixed $data,
        ?string $logger = null,
        ?array $_meta = null,
    ) {
        $params = [
            'level' => $level->value,
            'data' => $data,
        ];

        if(isset($logger)) $params['logger'] = $logger;

        parent::__construct('notifications/message', $params, $_meta);
    }
}
