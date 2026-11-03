<?php

namespace Fabricate\Contracts\Mechanism;

use Fabricate\Contracts\Schema\Messaging\MCPError;
use Fabricate\Contracts\Schema\Messaging\Notification;
use Fabricate\Contracts\Schema\Messaging\Request;
use Fabricate\Contracts\Schema\Messaging\Result;
use Fabricate\Mechanism\Decoding\DecodedError;
use Fabricate\Mechanism\Decoding\DecodedNotification;
use Fabricate\Mechanism\Decoding\DecodedRequest;
use Fabricate\Mechanism\Decoding\DecodedResponse;
use Fabricate\Mechanism\Hydration\HydrationException;

/** Builds one protocol version's schema classes from decoded messages. */
interface VersionHydrator
{
    /** @throws HydrationException When the method is unknown to this version or the params do not fit it. */
    public function request(DecodedRequest $message): Request;

    /** @throws HydrationException When the method is unknown to this version or the params do not fit it. */
    public function notification(DecodedNotification $message): Notification;

    /**
     * @param string $method The method of the request this response answers.
     * @throws HydrationException When the method is unknown to this version or the result does not fit it.
     */
    public function result(DecodedResponse $message, string $method): Result;

    /** @throws HydrationException When this version does not know the error code. */
    public function error(DecodedError $message): MCPError;
}
