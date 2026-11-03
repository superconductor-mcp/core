<?php

namespace Fabricate\Schema\V20251125\Messaging\Elicitation;

use Fabricate\Contracts\Schema\Messaging\ServerNotification;
use Fabricate\Schema\Common\Messaging\RPC\JSONRPCNotification;

final readonly class ElicitationCompleteNotification extends JSONRPCNotification implements ServerNotification
{
    public function __construct(
        string $elicitation_id,
    ) {
        parent::__construct("2.0", 'notifications/elicitation/complete', ['elicitationId' => $elicitation_id]);
    }
}
