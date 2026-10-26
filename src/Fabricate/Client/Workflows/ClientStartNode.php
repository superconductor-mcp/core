<?php

namespace Fabricate\Client\Workflows;

use Throwable;
use Voyager\Workflows\Node;
use Voyager\Workflows\SharedBag;
use Fabricate\Client\ClientBag;
use Fabricate\Client\MCPClient;
use Fabricate\Client\RequestFailed;
use Fabricate\Contracts\Client\Connection;
use Fabricate\NutsAndBolts\Messages\Errors\JsonRpcError;
use Fabricate\NutsAndBolts\Messages\MCPRequest;
use Fabricate\NutsAndBolts\Messages\MCPResponse;
use Fabricate\NutsAndBolts\Messages\MCPNotification;
use Fabricate\Mechanism\Workflows\LogIncomingMessage;
use Fabricate\NutsAndBolts\Messages\Ping\PingRequest;
use Fabricate\NutsAndBolts\Messages\Ping\PingResponse;
use Fabricate\NutsAndBolts\Messages\Errors\MethodNotFound;
use Fabricate\NutsAndBolts\Messages\Errors\InvalidRequest;
use Fabricate\NutsAndBolts\Messages\Responses\ReceivedResponse;
use Fabricate\NutsAndBolts\Messages\Initialize\InitializeResponse;
use Fabricate\NutsAndBolts\Messages\Tools\ToolListChangedNotification;
use Fabricate\NutsAndBolts\Messages\Logging\LoggingMessageNotification;
use Fabricate\NutsAndBolts\Messages\Prompts\PromptListChangedNotification;
use Fabricate\NutsAndBolts\Messages\Resources\ResourcesListChangedNotification;

// one batch of decoded lines from the server, in the order they arrived
class ClientStartNode extends Node
{
    public function __construct(
        protected array $mail,
        protected MCPClient $client,
        protected Connection $connection,
    ) {
        parent::__construct();
    }

    public function prep(SharedBag $shared): mixed
    {
        return $shared;
    }

    public function exec(mixed $prepRes): mixed
    {
        /** @var ClientBag $prepRes */
        foreach ($this->mail as $message) {
            if ($message instanceof ReceivedResponse) {
                $this->processResponse($message, $prepRes);
            } elseif ($message instanceof MCPRequest) {
                $this->processRequest($message, $prepRes);
            } elseif ($message instanceof MCPNotification) {
                $this->processNotification($message, $prepRes);
            } elseif ($message instanceof JsonRpcError) {
                // the decoder already built the right error with the right id: a server request this client
                // does not support gets it back; a notification or unreadable line is logged and never answered
                if ($message->replies()) {
                    $this->connection->send($message->toJson());
                } else {
                    app('log')->warning('mcp.client '.$this->client->name().' unreadable line', ['error' => $message->error]);
                }
            }
        }

        return null;
    }

    public function post(SharedBag $shared, mixed $prepRes, mixed $execRes): ?string
    {
        return null;
    }

    // the answer to something this client sent: settle the promise waiting on that id, then whatever the client does next with it
    protected function processResponse(ReceivedResponse $response, ClientBag $bag): void
    {
        $key = ClientBag::key($response->id);

        if (! isset($bag->pending[$key])) {
            app('log')->info('mcp.client '.$this->client->name().' response nobody is waiting for', ['id' => $response->id]);

            return;
        }

        [$promise, $response_class, $method] = $bag->pending[$key];
        $fill = $bag->filling[$key] ?? null;
        unset($bag->pending[$key], $bag->filling[$key]);

        try {
            if (! is_null($response->error)) {
                throw new RequestFailed("{$method}: {$response->error['message']}", $response->error['code'], $response->error['data'] ?? null);
            }

            $typed = $response_class::fromReceived($response);
        } catch (Throwable $e) {
            $promise->reject($e instanceof RequestFailed ? $e : new RequestFailed("{$method}: {$e->getMessage()}"));

            if ($response_class === InitializeResponse::class) {
                $this->client->fail($e->getMessage());
            } elseif (! is_null($fill)) {
                // a list the server refuses (say, templates on a server without any) keeps what was gathered; the client stays usable
                app('log')->warning('mcp.client '.$this->client->name().' '.$e->getMessage());
                $this->client->filled($fill[0], $fill[1]);
            }

            return;
        }

        $promise->resolve($typed);

        if ($typed instanceof InitializeResponse) {
            $this->client->handshakeDone($typed);
        } elseif (! is_null($fill)) {
            $this->continueFill($fill[0], $fill[1], $typed);
        }
    }

    // a page of a cache refill: gather it, then ask for the next page or hand the whole list over
    /** @param list<array> $so_far */
    protected function continueFill(string $kind, array $so_far, MCPResponse $page): void
    {
        $items = [...$so_far, ...$page->{MCPClient::LISTS[$kind][2]}];

        if (is_null($page->next_cursor)) {
            $this->client->filled($kind, $items);
        } else {
            $this->client->fill($kind, $page->next_cursor, $items);
        }
    }

    // a request from the server; with no client capabilities advertised, ping is the only one it may send
    protected function processRequest(MCPRequest $request, ClientBag $bag): void
    {
        if (! $bag->their_ids->receive($request->id)) {
            $this->connection->send((new InvalidRequest($request->id, "Request id [{$request->id}] has already been used."))->toJson());

            return;
        }

        new LogIncomingMessage($request)->dispatch($bag);

        $this->connection->send(
            $request instanceof PingRequest
                ? (new PingResponse($request->id))->toJson()
                : (new MethodNotFound($request->id, $request->method))->toJson()
        );
    }

    protected function processNotification(MCPNotification $notification, ClientBag $bag): void
    {
        new LogIncomingMessage($notification)->dispatch($bag);

        match (true) {
            $notification instanceof ToolListChangedNotification => $this->client->fill('tools'),
            $notification instanceof ResourcesListChangedNotification => [$this->client->fill('resources'), $this->client->fill('resource_templates')],
            $notification instanceof PromptListChangedNotification => $this->client->fill('prompts'),
            // the server's log line goes into this application's log at the level it was sent
            $notification instanceof LoggingMessageNotification => app('log')->log(
                $notification->level->value,
                'mcp.client '.$this->client->name().' '.($notification->logger ?? 'server'),
                is_array($notification->data) ? $notification->data : ['data' => $notification->data],
            ),
            // resources/updated and progress are logged by LogIncomingMessage above; nothing more to do for them yet
            default => null,
        };
    }
}
