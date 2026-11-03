<?php

namespace Fabricate\Schema\V20241105\Messaging\Cancellation;

use Fabricate\Contracts\Schema\Messaging\Cancellation\CancelledNotification as CancelledContract;
use Fabricate\Contracts\Schema\Messaging\ClientNotification;
use Fabricate\Contracts\Schema\Messaging\ServerNotification;
use Fabricate\Contracts\Schema\RequestId;
use Fabricate\Schema\V20241105\Messaging\Notification;

final readonly class CancelledNotification extends Notification implements CancelledContract, ClientNotification, ServerNotification
{
    public function __construct(
        RequestId $request_id,
        ?string $reason = null,
        ?array $_meta = null,
    ) {
        $params = ['requestId' => $request_id->id];
        if(isset($reason)) $params['reason'] = $reason;

        parent::__construct(
            'notifications/cancelled',
            $params,
            $_meta,
        );
    }
}
