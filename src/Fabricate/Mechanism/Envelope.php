<?php

namespace Fabricate\Mechanism;

use Fabricate\Contracts\Schema\Messaging\MCPError;
use Fabricate\Contracts\Schema\Messaging\Notification;
use Fabricate\Contracts\Schema\Messaging\Request;
use Fabricate\Contracts\Schema\Messaging\Result;
use Fabricate\Schema\Common\Messaging\RPC\JSONRPCRequest;
use Fabricate\Contracts\Schema\Messaging\RPC\JSONRPCMessage;
use Fabricate\Contracts\Schema\RequestId;
use Fabricate\Schema\Common\Messaging\Result as SchemaResult;
use Fabricate\Schema\V20241105;
use Fabricate\Schema\V20250326;
use Fabricate\Schema\V20250618;
use Fabricate\Schema\V20251125;
use Fabricate\Schema\V20260728;
use InvalidArgumentException;

/**
 * Wraps schema messages in their protocol version's JSON-RPC classes, so they can be sent.
 */
final class Envelope
{
    /**
     * A request's answer as that version's result or error response.
     */
    public static function response(string $protocol_version, RequestId $id, Result|MCPError $answer): JSONRPCMessage
    {
        if($answer instanceof MCPError)
        {
            ['code' => $code, 'message' => $message] = $error = $answer->toArray();
            $data = $error['data'] ?? null;

            return match($protocol_version) {
                '2024-11-05' => new V20241105\Messaging\RPC\JSONRPCError('2.0', $id, $code, $message, $data),
                '2025-03-26' => new V20250326\Messaging\RPC\JSONRPCError('2.0', $id, $code, $message, $data),
                '2025-06-18' => new V20250618\Messaging\RPC\JSONRPCError('2.0', $id, $code, $message, $data),
                '2025-11-25' => new V20251125\Messaging\RPC\JSONRPCErrorResponse('2.0', $id, new V20251125\Messaging\MCPError($code, $message, $data)),
                '2026-07-28' => new V20260728\Messaging\RPC\JSONRPCErrorResponse('2.0', $id, new V20260728\Messaging\MCPError($code, $message, $data)),
            };
        }

        // 2026-07-28's envelope takes its own result base; the others take the common one every schema result extends.
        if($protocol_version === '2026-07-28')
        {
            assert($answer instanceof V20260728\Messaging\Result);

            return new V20260728\Messaging\RPC\JSONRPCResultResponse('2.0', $id, $answer);
        }

        assert($answer instanceof SchemaResult);

        return match($protocol_version) {
            '2024-11-05' => new V20241105\Messaging\RPC\JSONRPCResponse('2.0', $id, $answer),
            '2025-03-26' => new V20250326\Messaging\RPC\JSONRPCResponse('2.0', $id, $answer),
            '2025-06-18' => new V20250618\Messaging\RPC\JSONRPCResponse('2.0', $id, $answer),
            '2025-11-25' => new V20251125\Messaging\RPC\JSONRPCResultResponse('2.0', $id, $answer),
        };
    }

    /**
     * A request as something sendable: from 2025-11-25 it already is, carrying its id; earlier versions wrap it in
     * their JSONRPCRequest with the id given here.
     */
    public static function request(string $protocol_version, Request $request, ?RequestId $id = null): JSONRPCRequest
    {
        if($request instanceof JSONRPCRequest) return $request;

        if(is_null($id)) throw new InvalidArgumentException("A {$protocol_version} request takes its id from the envelope: none was given for [{$request->method}].");

        return match($protocol_version) {
            '2024-11-05' => new V20241105\Messaging\RPC\JSONRPCRequest('2.0', $id, $request),
            '2025-03-26' => new V20250326\Messaging\RPC\JSONRPCRequest('2.0', $id, $request),
            '2025-06-18' => new V20250618\Messaging\RPC\JSONRPCRequest('2.0', $id, $request),
        };
    }

    /**
     * A notification as something sendable: from 2025-11-25 it already is; earlier versions wrap it in their JSONRPCNotification.
     */
    public static function notification(string $protocol_version, Notification $notification): JSONRPCMessage
    {
        if($notification instanceof JSONRPCMessage) return $notification;

        return match($protocol_version) {
            '2024-11-05' => new V20241105\Messaging\RPC\JSONRPCNotification('2.0', $notification),
            '2025-03-26' => new V20250326\Messaging\RPC\JSONRPCNotification('2.0', $notification),
            '2025-06-18' => new V20250618\Messaging\RPC\JSONRPCNotification('2.0', $notification),
        };
    }
}
