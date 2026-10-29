<?php

namespace Fabricate\Contracts\Client;

use Closure;
use Fabricate\Contracts\Mechanism\MCPEntity;
use Voyager\Contracts\IOPools\Promise;

interface MCPClient extends MCPEntity
{
    public function name(): string;

    public function status(): ClientStatus;

    public function lastError(): ?string;

    // resolves with this client once the handshake is done and every list is cached; rejects when connecting failed
    public function ready(): Promise;

    // true once the connection has fully ended, after close() or a failure
    public function closed(): bool;

    public function attach(Connection $connection): static;

    // spawn or dial the server, then open with initialize
    public function open(): void;

    public function disable(): void;

    // a deliberate end: nothing waiting gets an answer
    public function close(): void;

    // 2025-06-18: answers elicitation/create; set before open() so initialize declares it
    public function onElicit(Closure $handler): static;

    public function elicitHandler(): ?Closure;

    // what the handshake negotiated
    public function serverInfo(): array;

    public function serverCapabilities(): array;

    public function protocolVersion(): ?string;

    // the caches, every page
    public function tools(): array;

    public function resources(): array;

    public function resourceTemplates(): array;

    public function prompts(): array;

    /** @return Promise<\Fabricate\NutsAndBolts\Messages\Ping\PingResponse> */
    public function ping(): Promise;

    /** @return Promise<\Fabricate\NutsAndBolts\Messages\Tools\ListToolsResponse> */
    public function listTools(?string $cursor = null): Promise;

    /** @return Promise<\Fabricate\NutsAndBolts\Messages\Tools\CallToolResponse> */
    public function callTool(string $name, array $arguments = []): Promise;

    /** @return Promise<\Fabricate\NutsAndBolts\Messages\Resources\ListResourcesResponse> */
    public function listResources(?string $cursor = null): Promise;

    /** @return Promise<\Fabricate\NutsAndBolts\Messages\Resources\ListResourceTemplatesResponse> */
    public function listResourceTemplates(?string $cursor = null): Promise;

    /** @return Promise<\Fabricate\NutsAndBolts\Messages\Resources\ReadResourceResponse> */
    public function readResource(string $uri): Promise;

    /** @return Promise<\Fabricate\NutsAndBolts\Messages\Prompts\ListPromptsResponse> */
    public function listPrompts(?string $cursor = null): Promise;

    /** @return Promise<\Fabricate\NutsAndBolts\Messages\Prompts\GetPromptResponse> */
    public function getPrompt(string $name, array $arguments = []): Promise;
}
