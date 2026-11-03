<?php

namespace Fabricate\Mechanism\Signals;

use Fabricate\Mechanism\RequestContext;
use Fabricate\Schema\V20260728\Capabilities\Resources\SubscriptionFilter;

/**
 * Fired when a 2026-07-28 subscriptions/listen request is acknowledged. The MCPServer of that session keeps the
 * subscription and sends it the change notifications its filter agreed to, until it is cancelled or the server ends it.
 */
final readonly class Subscribed
{
    public function __construct(
        public RequestContext $context,
        public SubscriptionFilter $notifications,
    ) {}
}
