<?php

namespace Fabricate\Schema\V20241105\Messaging;

use Fabricate\Schema\Common\Messaging\Notification as BaseNotification;

readonly class Notification extends BaseNotification
{
    public function __construct(
        string $method,
        ?array $params = null,
        ?array $_meta = null,
    ) {
        if(isset($params) || isset($_meta))
        {
            $payload = $params ?? [];

            if(isset($_meta))
            {
                $payload['_meta'] = $_meta ?: (object) [];
            }

            $params = $payload;
        }

        parent::__construct($method, $params);
    }
}
