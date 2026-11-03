<?php

namespace Fabricate\Mechanism;

use Fabricate\Contracts\Core\SuperconductorMCPException;
use Fabricate\Contracts\Mechanism\DecodedMessage;
use Fabricate\Contracts\Schema\RPCErrorCode;
use Fabricate\Contracts\Schema\RequestId;
use Fabricate\Mechanism\Decoding\DecodedBatch;
use Fabricate\Mechanism\Decoding\DecodedError;
use Fabricate\Mechanism\Decoding\DecodedNotification;
use Fabricate\Mechanism\Decoding\DecodedRequest;
use Fabricate\Mechanism\Decoding\DecodedResponse;
use Fabricate\Mechanism\Decoding\InvalidMessage;
use JsonException;
use ReflectionException;

class MessageDecoder
{
    /**
     * Decodes a JSON-RPC payload read as raw text, such as one line from STDIN.
     * @throws ReflectionException
     */
    public static function fromWire(string $line): DecodedMessage|DecodedBatch
    {
        try {
            $data = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            return new InvalidMessage(null, RPCErrorCode::PARSE_ERROR, 'Parse error: '.$e->getMessage());
        }

        if (! is_array($data)) {
            return new InvalidMessage(null, RPCErrorCode::INVALID_REQUEST, 'A JSON-RPC payload must be an object or a batch array.');
        }

        return self::fromBody($data);
    }

    /**
     * Decodes a JSON-RPC payload that arrived already decoded, such as an HTTP request body.
     *
     * @param array $line The payload as json_decode() returns it with associative arrays.
     * @throws ReflectionException
     */
    public static function fromBody(array $line): DecodedMessage|DecodedBatch
    {
        // json_decode() maps both {} and [] to an empty array, and neither is a valid payload.
        if ($line === []) {
            return new InvalidMessage(null, RPCErrorCode::INVALID_REQUEST, 'A JSON-RPC payload must not be empty.');
        }

        if (! array_is_list($line)) {
            return self::message($line);
        }

        $max_batch_size = config('mcp.max_batch_size');
        if (! is_int($max_batch_size) || $max_batch_size < 1) {
            throw new SuperconductorMCPException(static::class.'- mcp.max_batch_size must be a positive integer.');
        }

        if (count($line) > $max_batch_size) {
            return new InvalidMessage(null, RPCErrorCode::INVALID_REQUEST, sprintf('A batch of %d messages exceeds the maximum of %d.', count($line), $max_batch_size));
        }

        return new DecodedBatch(...array_map(
            fn (mixed $item): DecodedMessage => is_array($item) && $item !== [] && ! array_is_list($item)
                ? self::message($item)
                : new InvalidMessage(null, RPCErrorCode::INVALID_REQUEST, 'A batch item must be a JSON-RPC message object.'),
            $line,
        ));
    }

    /**
     * Checks one message object's envelope and classifies it by the members it carries.
     *
     * @param array $data One message object from the payload.
     */
    private static function message(array $data): DecodedMessage
    {
        $id = $data['id'] ?? null;
        $request_id = is_string($id) || is_int($id) ? new RequestId($id) : null;

        if (($data['jsonrpc'] ?? null) !== '2.0') {
            return new InvalidMessage($request_id, RPCErrorCode::INVALID_REQUEST, 'The "jsonrpc" member must be exactly "2.0".');
        }

        if (array_key_exists('method', $data)) {
            if (! is_string($data['method'])) {
                return new InvalidMessage($request_id, RPCErrorCode::INVALID_REQUEST, 'The "method" member must be a string.');
            }

            if (array_key_exists('id', $data) && is_null($request_id)) {
                return new InvalidMessage(null, RPCErrorCode::INVALID_REQUEST, 'The "id" member of a request must be a string or an integer.');
            }

            $params = $data['params'] ?? null;
            if (! is_null($params) && (! is_array($params) || ($params !== [] && array_is_list($params)))) {
                return new InvalidMessage($request_id, RPCErrorCode::INVALID_REQUEST, 'The "params" member must be an object.');
            }

            return is_null($request_id)
                ? new DecodedNotification($data['method'], $params)
                : new DecodedRequest($request_id, $data['method'], $params);
        }

        $has_result = array_key_exists('result', $data);
        if ($has_result === array_key_exists('error', $data)) {
            return new InvalidMessage($request_id, RPCErrorCode::INVALID_REQUEST, 'A message must carry "method", or exactly one of "result" and "error".');
        }

        if ($has_result) {
            return is_null($request_id)
                ? new InvalidMessage(null, RPCErrorCode::INVALID_REQUEST, 'The "id" member of a response must be a string or an integer.')
                : new DecodedResponse($request_id, $data['result']);
        }

        $error = is_array($data['error']) ? $data['error'] : [];
        $code = $error['code'] ?? null;
        $message = $error['message'] ?? null;
        if (! is_int($code) || ! is_string($message)) {
            return new InvalidMessage($request_id, RPCErrorCode::INVALID_REQUEST, 'The "error" member must carry an integer "code" and a string "message".');
        }

        // An error may carry a null id, when the peer could not read the id of the request it rejects.
        if (! is_null($id) && is_null($request_id)) {
            return new InvalidMessage(null, RPCErrorCode::INVALID_REQUEST, 'The "id" member of an error must be a string, an integer or null.');
        }

        return new DecodedError($request_id, $code, $message, $error['data'] ?? null);
    }
}
