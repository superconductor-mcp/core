<?php

namespace Fabricate\NutsAndBolts;

use Fabricate\Contracts\NutsAndBolts\MCPError;
use Fabricate\NutsAndBolts\Messages\Initialize;
use Fabricate\NutsAndBolts\Messages\MCPMessage;
use Fabricate\NutsAndBolts\Messages\Ping\PingRequest;
use Fabricate\NutsAndBolts\Messages\Resources\ListResourcesRequest;
use Fabricate\NutsAndBolts\Messages\Resources\ListResourceTemplatesRequest;
use Fabricate\NutsAndBolts\Messages\Resources\ReadResourceRequest;
use Fabricate\NutsAndBolts\Messages\Responses\ReceivedResponse;
use Fabricate\NutsAndBolts\Messages\Errors\ParseError;
use Fabricate\NutsAndBolts\Messages\Tools\CallToolRequest;
use Fabricate\NutsAndBolts\Messages\Errors\MethodNotFound;
use Fabricate\NutsAndBolts\Messages\Errors\InvalidRequest;
use Fabricate\NutsAndBolts\Messages\Tools\ListToolsRequest;
use Fabricate\NutsAndBolts\Messages\Errors\UnknownNotification;
use Fabricate\NutsAndBolts\Messages\Resources\SubscribeRequest;
use Fabricate\NutsAndBolts\Messages\Resources\UnsubscribeRequest;
use Fabricate\NutsAndBolts\Messages\Logging\SetLevelRequest;
use Fabricate\NutsAndBolts\Messages\Completion\CompleteRequest;
use Fabricate\NutsAndBolts\Messages\Prompts\ListPromptsRequest;
use Fabricate\NutsAndBolts\Messages\Prompts\GetPromptRequest;
use Fabricate\NutsAndBolts\Messages\MCPRequest;
use Fabricate\NutsAndBolts\Messages\Errors\InvalidParams;
use Fabricate\NutsAndBolts\Messages\Cancellation\CancelledNotification;
use Fabricate\NutsAndBolts\Messages\Tools\ToolListChangedNotification;
use Fabricate\NutsAndBolts\Messages\Resources\ResourcesListChangedNotification;
use Fabricate\NutsAndBolts\Messages\Prompts\PromptListChangedNotification;
use Fabricate\NutsAndBolts\Messages\Resources\ResourceUpdatedNotification;
use Fabricate\NutsAndBolts\Messages\Logging\LoggingMessageNotification;
use Fabricate\NutsAndBolts\Messages\Progress\ProgressNotification;

class LineDecoder
{
    public static function decodeString(string $line): MCPMessage|MCPError
    {
        $decoded = json_decode($line, true);

        if (! is_array($decoded) || $decoded === [] || array_is_list($decoded)) {
            return static::handleUnknownMessage($line);
        }

        if(isset( $decoded['method']))
        {
            // every message, request or notification, must say it is JSON-RPC 2.0
            if (($decoded['jsonrpc'] ?? null) !== '2.0') {
                return static::handleUnknownMessage($line);
            }

            // no id: a notification. None are known yet, so each one is unknown and never answered.
            if (! array_key_exists('id', $decoded)) {
                return match($decoded['method'])
                {
                    'notifications/initialized' => new Initialize\InitializedNotification(),
                    'notifications/cancelled' => CancelledNotification::generate($decoded['params'] ?? null) ?? static::handleUnknownMessage($line),
                    // the notifications a server sends its client
                    'notifications/tools/list_changed' => new ToolListChangedNotification(),
                    'notifications/resources/list_changed' => new ResourcesListChangedNotification(),
                    'notifications/prompts/list_changed' => new PromptListChangedNotification(),
                    'notifications/resources/updated' => ResourceUpdatedNotification::generate($decoded['params'] ?? null) ?? static::handleUnknownMessage($line),
                    'notifications/message' => LoggingMessageNotification::generate($decoded['params'] ?? null) ?? static::handleUnknownMessage($line),
                    'notifications/progress' => ProgressNotification::generate($decoded['params'] ?? null) ?? static::handleUnknownMessage($line),
                    default => static::handleUnknownMessage($line),
                };
            }

            $id = $decoded['id'];
            $method = $decoded['method'];
            $params = $decoded['params'] ?? null;

            // a progressToken in params._meta asks for notifications/progress; it must be a string or an integer
            $progress_token = is_array($params) ? ($params['_meta']['progressToken'] ?? null) : null;

            if (! is_null($progress_token) && ! is_string($progress_token) && ! is_int($progress_token)) {
                return new InvalidParams(is_string($id) || is_int($id) ? $id : null, 'params._meta.progressToken must be a string or an integer.');
            }

            // This is a request
            $request = match($method)
            {
                'initialize' => Initialize\InitializeRequest::generate($id, $method, $params),
                'ping' => PingRequest::generate($id, $method, $params),
                'tools/list' => ListToolsRequest::generate($id, $method, $params),
                'tools/call' => CallToolRequest::generate($id, $method, $params),
                'resources/list' => ListResourcesRequest::generate($id, $method, $params),
                'resources/read' => ReadResourceRequest::generate($id, $method, $params),
                'resources/templates/list' => ListResourceTemplatesRequest::generate($id, $method, $params),
                'resources/subscribe' => SubscribeRequest::generate($id, $method, $params),
                'resources/unsubscribe' => UnsubscribeRequest::generate($id, $method, $params),
                'logging/setLevel' => SetLevelRequest::generate($id, $method, $params),
                'completion/complete' => CompleteRequest::generate($id, $method, $params),
                'prompts/list' => ListPromptsRequest::generate($id, $method, $params),
                'prompts/get' => GetPromptRequest::generate($id, $method, $params),
                default => static::handleUnknownMessage($line),
            };

            if ($request instanceof MCPRequest && ! is_null($progress_token)) {
                RequestMeta::rememberProgressToken($request, $progress_token);
            }

            return $request;
        }
        elseif(array_key_exists('id', $decoded) && (array_key_exists('result', $decoded) || array_key_exists('error', $decoded)))
        {
            // no method but an id and a result or error: the other side answering a request this side sent
            return ReceivedResponse::generate($decoded);
        }
        else
        {
            return static::handleUnknownMessage($line);
        }
    }

    public static function handleUnknownMessage(string $line): MCPError
    {
        $decoded = json_decode($line, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new ParseError(json_last_error_msg());
        }

        if (! is_array($decoded) || $decoded === [] || array_is_list($decoded)) {
            return new InvalidRequest(null, 'A message must be a JSON object.');
        }

        $id = $decoded['id'] ?? null;
        $readable_id = is_string($id) || is_int($id) ? $id : null;
        $method = $decoded['method'] ?? null;

        if (($decoded['jsonrpc'] ?? null) !== '2.0') {
            return new InvalidRequest($readable_id, 'jsonrpc must be "2.0".');
        }

        if (! is_string($method)) {
            return new InvalidRequest($readable_id, 'method must be a string.');
        }

        if (! array_key_exists('id', $decoded)) {
            return new UnknownNotification($method);
        }

        if (is_null($readable_id)) {
            return new InvalidRequest(null, 'id must be a string or an integer.');
        }

        return new MethodNotFound($readable_id, $method);
    }
}