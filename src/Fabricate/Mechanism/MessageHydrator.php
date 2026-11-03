<?php

namespace Fabricate\Mechanism;

use Fabricate\Contracts\Mechanism\DecodedMessage;
use Fabricate\Contracts\Mechanism\VersionHydrator;
use Fabricate\Contracts\Schema\Messaging\MCPError;
use Fabricate\Contracts\Schema\Messaging\Notification;
use Fabricate\Contracts\Schema\Messaging\Request;
use Fabricate\Contracts\Schema\Messaging\Result;
use Fabricate\Contracts\Schema\ProtocolVersion;
use Fabricate\Contracts\Schema\RPCErrorCode;
use Fabricate\Mechanism\Decoding\DecodedError;
use Fabricate\Mechanism\Decoding\DecodedNotification;
use Fabricate\Mechanism\Decoding\DecodedRequest;
use Fabricate\Mechanism\Decoding\DecodedResponse;
use Fabricate\Mechanism\Decoding\InvalidMessage;
use Fabricate\Mechanism\Hydration\HydrationException;
use Fabricate\Mechanism\Hydration\NotThisVersion;
use Fabricate\Mechanism\Hydration\V20241105Hydrator;
use Fabricate\Mechanism\Hydration\V20250326Hydrator;
use Fabricate\Mechanism\Hydration\V20250618Hydrator;
use Fabricate\Mechanism\Hydration\V20251125Hydrator;
use Fabricate\Mechanism\Hydration\V20260728Hydrator;
use Fabricate\Schema\V20260728\Messaging\MCPError as HydrationError;

/**
 * Turns a decoded message into the schema class it is, from whichever protocol version it fits.
 * Versions are tried newest first; the first whose shape the message satisfies wins.
 * A message no version accepts comes back as an MCPError that names the problem.
 */
final class MessageHydrator
{
    /** @var list<class-string<VersionHydrator>> Newest first, so a message takes its most recent shape. */
    private const array HYDRATORS = [
        V20260728Hydrator::class,
        V20251125Hydrator::class,
        V20250618Hydrator::class,
        V20250326Hydrator::class,
        V20241105Hydrator::class,
    ];

    /**
     * @param string|null $request_method For a response, the method of the request it answers; responses carry none.
     */
    public static function hydrate(DecodedMessage $message, ?string $request_method = null): Request|Notification|Result|MCPError
    {
        return self::hydrateWith(self::HYDRATORS, $message, $request_method);
    }

    /**
     * Hydrates the message as the given protocol version only, as a session does once its version is negotiated.
     *
     * @param string|null $request_method For a response, the method of the request it answers; responses carry none.
     */
    public static function hydrateAs(DecodedMessage $message, string $protocol_version, ?string $request_method = null): Request|Notification|Result|MCPError
    {
        $hydrator = match(ProtocolVersion::tryFrom($protocol_version)) {
            ProtocolVersion::V20241105 => V20241105Hydrator::class,
            ProtocolVersion::V20250326 => V20250326Hydrator::class,
            ProtocolVersion::V20250618 => V20250618Hydrator::class,
            ProtocolVersion::V20251125 => V20251125Hydrator::class,
            ProtocolVersion::V20260728 => V20260728Hydrator::class,
            null => null,
        };

        if(is_null($hydrator))
        {
            return new HydrationError(RPCErrorCode::INVALID_PARAMS->value, "[{$protocol_version}] is not a supported protocol version.");
        }

        return self::hydrateWith([$hydrator], $message, $request_method);
    }

    /**
     * @param list<class-string<VersionHydrator>> $hydrators Tried in order; the first that accepts the message wins.
     */
    private static function hydrateWith(array $hydrators, DecodedMessage $message, ?string $request_method): Request|Notification|Result|MCPError
    {
        if($message instanceof InvalidMessage)
        {
            return new HydrationError($message->code->value, $message->message);
        }

        if($message instanceof DecodedResponse && is_null($request_method))
        {
            return new HydrationError(RPCErrorCode::INVALID_REQUEST->value, "No request with id [{$message->id->id}] is awaiting a response.");
        }

        $failure = null;

        foreach($hydrators as $hydrator_class)
        {
            $hydrator = new $hydrator_class();

            try {
                return match(true) {
                    $message instanceof DecodedRequest => $hydrator->request($message),
                    $message instanceof DecodedNotification => $hydrator->notification($message),
                    $message instanceof DecodedResponse => $hydrator->result($message, $request_method),
                    $message instanceof DecodedError => $hydrator->error($message),
                    default => throw new HydrationException('Unknown decoded message type '.get_class($message).'.', RPCErrorCode::INVALID_REQUEST),
                };
            } catch (HydrationException $e) {
                // When no version claims the message, the last refusal wins: the handshake versions name the version the message asked for.
                if(is_null($failure) || self::rank($e) > self::rank($failure) || ($e instanceof NotThisVersion && $failure instanceof NotThisVersion)) $failure = $e;
            }
        }

        return new HydrationError($failure->error_code->value, $failure->getMessage());
    }

    /** A shape failure from a version the message belongs to outranks "unknown method", which outranks "not this version". */
    private static function rank(HydrationException $failure): int
    {
        return match(true) {
            $failure instanceof NotThisVersion => 0,
            $failure->error_code === RPCErrorCode::METHOD_NOT_FOUND => 1,
            default => 2,
        };
    }
}
