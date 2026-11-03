<?php

namespace Fabricate\Schema\V20251125\Messaging\Params;

final readonly class ResourceUpdatedNotificationParams extends NotificationParams
{
    public function __construct(
        public string $uri,
        ?array $_meta = null,
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
