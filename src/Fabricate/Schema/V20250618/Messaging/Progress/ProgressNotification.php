<?php

namespace Fabricate\Schema\V20250618\Messaging\Progress;

use Fabricate\Contracts\Schema\Messaging\ClientNotification;
use Fabricate\Contracts\Schema\Messaging\Progress\ProgressNotification as ProgressContract;
use Fabricate\Contracts\Schema\Messaging\ServerNotification;
use Fabricate\Contracts\Schema\ProgressToken;
use Fabricate\Schema\V20250618\Messaging\Notification;

final readonly class ProgressNotification extends Notification implements ProgressContract, ClientNotification, ServerNotification
{
    public function __construct(
        ProgressToken $progress_token,
        int|float $progress,
        int|float|null $total = null,
        ?string $message = null,
        ?array $_meta = null,
    ) {
        $params = [
            'progressToken' => $progress_token->token,
            'progress' => $progress,
        ];

        if(isset($total)) $params['total'] = $total;
        if(isset($message)) $params['message'] = $message;

        parent::__construct('notifications/progress', $params, $_meta);
    }
}
