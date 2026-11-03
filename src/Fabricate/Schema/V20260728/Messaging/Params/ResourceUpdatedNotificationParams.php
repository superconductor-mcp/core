<?php

namespace Fabricate\Schema\V20260728\Messaging\Params;

use Fabricate\Schema\V20260728\Messaging\MetaObjects\NotificationMetaObject;

final readonly class ResourceUpdatedNotificationParams extends NotificationParams
{
    public function __construct(
        public string $uri,
        ?NotificationMetaObject $_meta = null,
    ) {
        parent::__construct($_meta);
    }

    public function toArray(): array
    {
        $results = parent::toArray();

        $results['uri'] = $this->uri;

        return $results;
    }
}
