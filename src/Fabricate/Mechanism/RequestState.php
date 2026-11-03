<?php

namespace Fabricate\Mechanism;

use Fabricate\Contracts\Core\SuperconductorMCPException;
use Fabricate\Contracts\Mechanism\MCPMechanismException;
use Fabricate\Contracts\Schema\RPCErrorCode;
use JsonException;

/**
 * The requestState of an InputRequiredResult. The client must echo it untouched, but it is attacker-controlled input,
 * so it is sealed with an HMAC under the app key and bound to the request it was issued for (the method and its tool,
 * prompt or resource) and to an expiry of mcp.request_state.ttl seconds. A retry whose state fails any check is refused.
 */
final class RequestState
{
    /**
     * @throws JsonException
     */
    public static function seal(mixed $state, string $method, string $target): string
    {
        $payload = self::encode(json_encode([
            'state' => $state,
            'method' => $method,
            'target' => $target,
            'expires' => time() + (int) config('mcp.request_state.ttl', 300),
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));

        return $payload.'.'.self::encode(self::mac($payload));
    }

    /**
     * The state sealed into a retry's requestState, if it is genuine, unexpired, and was issued for this request.
     */
    public static function open(string $sealed, string $method, string $target): mixed
    {
        [$payload, $mac] = array_pad(explode('.', $sealed, 2), 2, '');
        $decoded = hash_equals(self::encode(self::mac($payload)), $mac) ? json_decode(self::decode($payload), true) : null;

        if(! is_array($decoded) || ($decoded['method'] ?? null) !== $method || ($decoded['target'] ?? null) !== $target || ($decoded['expires'] ?? 0) < time()) {
            throw new SuperconductorMCPException('requestState failed verification: it was altered, has expired, or belongs to another request.', RPCErrorCode::INVALID_PARAMS->value);
        }

        return $decoded['state'];
    }

    private static function mac(string $payload): string
    {
        $key = (string) config('app.key');

        if($key === '') throw new MCPMechanismException('Sealing requestState needs an app key: set APP_KEY.');

        return hash_hmac('sha256', $payload, $key, true);
    }

    private static function encode(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }

    private static function decode(string $text): string
    {
        return (string) base64_decode(strtr($text, '-_', '+/'));
    }
}
