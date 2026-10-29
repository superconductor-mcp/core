<?php

namespace Fabricate\Client;

use Closure;
use Fabricate\Contracts\Client\ClientStatus;
use Fabricate\NutsAndBolts\Capabilities;
use Fabricate\NutsAndBolts\Messages\Elicitation\ElicitAnswer;
use Fabricate\NutsAndBolts\Messages\Elicitation\ElicitRequest;
use Throwable;
use Voyager\Workflows\Flow;
use Fabricate\Mechanism\MCPEntity;
use Voyager\Contracts\IOPools\Loop;
use Voyager\Contracts\IOPools\Promise;
use Fabricate\Contracts\Client\Connection;
use Fabricate\Contracts\Client\MCPClient as ClientContract;
use Fabricate\Client\Workflows\ClientStartNode;
use Fabricate\NutsAndBolts\Messages\MCPRequest;
use Fabricate\NutsAndBolts\Messages\MCPResponse;
use Fabricate\NutsAndBolts\Messages\Ping\PingRequest;
use Fabricate\NutsAndBolts\Messages\Ping\PingResponse;
use Fabricate\NutsAndBolts\Messages\Tools\CallToolRequest;
use Fabricate\NutsAndBolts\Messages\Tools\CallToolResponse;
use Fabricate\NutsAndBolts\Messages\Tools\ListToolsRequest;
use Fabricate\NutsAndBolts\Messages\Tools\ListToolsResponse;
use Fabricate\NutsAndBolts\Messages\Prompts\GetPromptRequest;
use Fabricate\NutsAndBolts\Messages\Prompts\GetPromptResponse;
use Fabricate\NutsAndBolts\Messages\Prompts\ListPromptsRequest;
use Fabricate\NutsAndBolts\Messages\Prompts\ListPromptsResponse;
use Fabricate\NutsAndBolts\Messages\Initialize\InitializeRequest;
use Fabricate\NutsAndBolts\Messages\Initialize\InitializeResponse;
use Fabricate\NutsAndBolts\Messages\Resources\ReadResourceRequest;
use Fabricate\NutsAndBolts\Messages\Resources\ReadResourceResponse;
use Fabricate\NutsAndBolts\Messages\Resources\ListResourcesRequest;
use Fabricate\NutsAndBolts\Messages\Resources\ListResourcesResponse;
use Fabricate\NutsAndBolts\Messages\Initialize\InitializedNotification;
use Fabricate\NutsAndBolts\Messages\Resources\ListResourceTemplatesRequest;
use Fabricate\NutsAndBolts\Messages\Resources\ListResourceTemplatesResponse;

// one connection to one server, living inside the application's own event loop
class MCPClient extends MCPEntity implements ClientContract
{
    public const string VERSION = '0.11.0';

    /** each list kind the client caches: its request class, response class, and the response property holding the page */
    public const array LISTS = [
        'tools' => [ListToolsRequest::class, ListToolsResponse::class, 'tools'],
        'resources' => [ListResourcesRequest::class, ListResourcesResponse::class, 'resources'],
        'resource_templates' => [ListResourceTemplatesRequest::class, ListResourceTemplatesResponse::class, 'resourceTemplates'],
        'prompts' => [ListPromptsRequest::class, ListPromptsResponse::class, 'prompts'],
    ];

    protected array $roots = [];
    protected array $sampling = [];

    protected ClientBag $bag;

    protected ?Connection $connection = null;

    protected Promise $ready;

    // settled by this client; the promise's own flag only turns once the engine has flushed its callbacks, a frame later
    private bool $ready_settled = false;

    /** @var Closure(ElicitRequest, MCPClient): ElicitAnswer|null answers elicitation/create; set before open() so initialize declares it */
    private ?Closure $elicit = null;

    public function __construct(
        protected readonly string $name,
        protected readonly Loop $event_loop,
    ) {
        $this->bag = new ClientBag();
        $this->ready = $this->event_loop->promise();
        $this->entity_info = ['name' => (string) config('app.name', 'Venusian'), 'version' => self::VERSION];
    }

    // 2025-06-18: the server may ask the user something mid-call; the handler shows the question and returns the answer
    public function onElicit(Closure $handler): static
    {
        $this->elicit = $handler;
        $this->entity_capabilities['elicitation'] = [];

        return $this;
    }

    public function elicitHandler(): ?Closure
    {
        return $this->elicit;
    }

    public function attach(Connection $connection): static
    {
        $this->connection = $connection;

        return $this;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function status(): ClientStatus
    {
        return $this->bag->status;
    }

    // resolves with this client once the handshake is done and every list is cached; rejects when connecting failed
    public function ready(): Promise
    {
        return $this->ready;
    }

    // true once the connection has fully ended, after close() or a failure
    public function closed(): bool
    {
        return $this->connection->closed();
    }

    public function lastError(): ?string
    {
        return $this->bag->error;
    }

    public function serverInfo(): array
    {
        return $this->bag->server_info;
    }

    public function serverCapabilities(): array
    {
        return $this->bag->server_capabilities;
    }

    public function protocolVersion(): ?string
    {
        return $this->bag->protocol_version;
    }

    public function tools(): array
    {
        return $this->bag->tools;
    }

    public function resources(): array
    {
        return $this->bag->resources;
    }

    public function resourceTemplates(): array
    {
        return $this->bag->resource_templates;
    }

    public function prompts(): array
    {
        return $this->bag->prompts;
    }

    // spawn or dial the server, then open with initialize; the deadline covers both
    public function open(): void
    {
        if ($this->bag->status !== ClientStatus::NotConnected) {
            return;
        }

        $this->bag->status = ClientStatus::Connecting;

        try {
            $this->connection->open();
        } catch (Throwable $e) {
            $this->fail($e->getMessage());

            return;
        }

        $timeout = (float) config('mcp.client.connect_timeout', 20);
        $this->bag->connect_deadline = $this->event_loop->at($timeout, function () use ($timeout): void {
            if ($this->bag->status === ClientStatus::Connecting) {
                $this->fail("did not finish the handshake within {$timeout}s.");
            }
        });

        // the client proposes the latest version it speaks; versions are ISO dates, so the greatest string is the latest
        $this->request(
            new InitializeRequest($this->nextId(), (string) max(config('mcp.servers.supported_mcp_versions', ['2024-11-05'])), $this->entity_capabilities, $this->entity_info),
            InitializeResponse::class,
        );
    }

    public function disable(): void
    {
        $this->bag->status = ClientStatus::Disabled;
    }

    // a deliberate end: nothing waiting gets an answer, and the transport winds the server down in its own order
    public function close(): void
    {
        if (in_array($this->bag->status, [ClientStatus::NotConnected, ClientStatus::Disabled], true)) {
            return;
        }

        $this->bag->status = ClientStatus::NotConnected;
        $this->settleDown('the connection was closed.');
    }

    public function nextId(): int
    {
        return $this->bag->own_ids->next();
    }

    /**
     * @template T of MCPResponse
     * @param class-string<T> $response_class
     * @return Promise<T>
     */
    public function request(MCPRequest $request, string $response_class): Promise
    {
        $promise = $this->event_loop->promise();

        // before the handshake ends only initialize and ping may go out; after a failure or close nothing may
        $allowed = match ($this->bag->status) {
            ClientStatus::Connected => true,
            ClientStatus::Connecting => $request instanceof InitializeRequest || $request instanceof PingRequest,
            default => false,
        };

        if (! $allowed) {
            $promise->reject(new RequestFailed("{$request->method}: client [{$this->name}] is {$this->bag->status->value}."));

            return $promise;
        }

        // only what the server declared may be asked for
        $needs = $this->bag->status === ClientStatus::Connected ? Capabilities::requiredBy($request->method, $this->bag->protocol_version) : null;

        if (! is_null($needs) && ! Capabilities::declares($this->bag->server_capabilities, $needs)) {
            $promise->reject(new RequestFailed("{$request->method}: server [{$this->name}] did not declare ".implode('.', $needs).'.'));

            return $promise;
        }

        $this->bag->pending[ClientBag::key($request->id)] = [$promise, $response_class, $request->method];
        $this->connection->send($request->toJson());

        return $promise;
    }

    /** @return Promise<PingResponse> */
    public function ping(): Promise
    {
        return $this->request(new PingRequest($this->nextId()), PingResponse::class);
    }

    // one page, as the server sends it; the cached tools() hold every page
    /** @return Promise<ListToolsResponse> */
    public function listTools(?string $cursor = null): Promise
    {
        return $this->request(new ListToolsRequest($this->nextId(), $cursor), ListToolsResponse::class);
    }

    /** @return Promise<CallToolResponse> */
    public function callTool(string $name, array $arguments = []): Promise
    {
        return $this->request(new CallToolRequest($this->nextId(), $name, $arguments), CallToolResponse::class);
    }

    /** @return Promise<ListResourcesResponse> */
    public function listResources(?string $cursor = null): Promise
    {
        return $this->request(new ListResourcesRequest($this->nextId(), $cursor), ListResourcesResponse::class);
    }

    /** @return Promise<ListResourceTemplatesResponse> */
    public function listResourceTemplates(?string $cursor = null): Promise
    {
        return $this->request(new ListResourceTemplatesRequest($this->nextId(), $cursor), ListResourceTemplatesResponse::class);
    }

    /** @return Promise<ReadResourceResponse> */
    public function readResource(string $uri): Promise
    {
        return $this->request(new ReadResourceRequest($this->nextId(), $uri), ReadResourceResponse::class);
    }

    /** @return Promise<ListPromptsResponse> */
    public function listPrompts(?string $cursor = null): Promise
    {
        return $this->request(new ListPromptsRequest($this->nextId(), $cursor), ListPromptsResponse::class);
    }

    /** @return Promise<GetPromptResponse> */
    public function getPrompt(string $name, array $arguments = []): Promise
    {
        return $this->request(new GetPromptRequest($this->nextId(), $name, $arguments), GetPromptResponse::class);
    }

    // the connection hands over each batch of decoded lines; a Flow works through it like the server's does
    public function receive(array $messages): void
    {
        new Flow(new ClientStartNode($messages, $this, $this->connection))->run($this->bag);
    }

    // the connection ended on its own: the server exited, or its pipe closed
    public function connectionClosed(string $reason): void
    {
        if (in_array($this->bag->status, [ClientStatus::Connecting, ClientStatus::Connected], true)) {
            $this->fail($reason);
        }
    }

    // refill one cache: asks for the first page, or the page after $cursor with the pages gathered so far
    /** @param list<array> $so_far */
    public function fill(string $kind, ?string $cursor = null, array $so_far = []): void
    {
        [$request_class, $response_class] = self::LISTS[$kind];
        $request = new $request_class($this->nextId(), $cursor);

        $this->bag->filling[ClientBag::key($request->id)] = [$kind, $so_far];
        $this->request($request, $response_class);
    }

    // the last page landed, or the server refused the list: what was gathered becomes the cache
    /** @param list<array> $items */
    public function filled(string $kind, array $items): void
    {
        $this->bag->{$kind} = $items;
        unset($this->bag->initial_lists[$kind]);

        if ($this->bag->initial_lists === [] && $this->bag->status === ClientStatus::Connected) {
            $this->becomeReady();
        }
    }

    // the server answered initialize: check the version, finish the handshake, fill the caches it advertises
    public function handshakeDone(InitializeResponse $response): void
    {
        $supported = config('mcp.servers.supported_mcp_versions', ['2024-11-05']);

        if (! in_array($response->protocol_version, $supported, true)) {
            $this->fail("server speaks protocol {$response->protocol_version}; this client speaks ".implode(', ', $supported).'.');

            return;
        }

        $this->bag->protocol_version = $response->protocol_version;
        $this->bag->server_capabilities = $response->capabilities;
        $this->bag->server_info = $response->server_info;

        $this->connection->send((new InitializedNotification())->toJson());
        $this->bag->status = ClientStatus::Connected;

        $kinds = [];

        if (isset($response->capabilities['tools'])) {
            $kinds[] = 'tools';
        }

        if (isset($response->capabilities['resources'])) {
            $kinds[] = 'resources';
            $kinds[] = 'resource_templates';
        }

        if (isset($response->capabilities['prompts'])) {
            $kinds[] = 'prompts';
        }

        $this->bag->initial_lists = array_fill_keys($kinds, true);

        if ($kinds === []) {
            $this->becomeReady();
        }

        foreach ($kinds as $kind) {
            $this->fill($kind);
        }
    }

    public function fail(string $why): void
    {
        $this->bag->status = ClientStatus::Error;
        $this->bag->error = $why;
        app('log')->error('mcp.client '.$this->name.' '.$why);

        $this->settleDown($why);

        if (! $this->ready_settled) {
            $this->ready_settled = true;
            $this->ready->reject(new RequestFailed("client [{$this->name}] {$why}"));
        }
    }

    protected function becomeReady(): void
    {
        $this->bag->connect_deadline?->cancel();
        $this->bag->connect_deadline = null;

        if (! $this->ready_settled) {
            $this->ready_settled = true;
            $this->ready->resolve($this);
        }
    }

    // common to a failure and a deliberate close: stop the deadline, answer everything waiting, end the connection
    private function settleDown(string $why): void
    {
        $this->bag->connect_deadline?->cancel();
        $this->bag->connect_deadline = null;
        $this->bag->filling = [];

        [$pending, $this->bag->pending] = [$this->bag->pending, []];

        foreach ($pending as [$promise, , $method]) {
            $promise->reject(new RequestFailed("{$method}: {$why}"));
        }

        $this->connection->close();
    }
}
