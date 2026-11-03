<?php

namespace App\Mcp\Servers;
use Fabricate\Contracts\Mechanism\Attributes\ServerConfig;
use Fabricate\Server\ServerConfiguration;

#[ServerConfig('echo', 'Stop repeating yourself. .flesruoy gnitaeper potS', '0.11.0')]
class EchoServer extends ServerConfiguration
{
    protected bool|array $tools = [
        'listChanged' => true
    ];

    protected bool|array $prompts = [
        'listChanged' => true
    ];

    protected bool|array $resources = [
        'subscribe' => true,
        'listChanged' => true,
    ];

    protected bool $completions = true;

    protected bool $logging = true;

    protected bool|array $tasks = [
        'list' => [],
        'cancel' => [],
        'requests' => ['tools' => ['call' => true]],
    ];

    protected array $supported_protocol_versions = [
        '2024-11-05', '2025-03-26','2025-06-18','2025-11-25','2026-07-28'
    ];

    /**
     * @return list<string> The names of the tools this server offers, as their #[ToolCall] attributes give them.
     */
    public function tools(mixed ...$params): array
    {
        return ['echo', 'shout', 'enhashen', 'whisper', 'ponder'];
    }

    /**
     * @return list<string> The names of the prompts this server offers, as their #[PromptCall] attributes give them.
     */
    public function prompts(mixed ...$params): array
    {
        return ['greet'];
    }

    /**
     * @return list<string> The URIs and URI templates of the resources this server offers.
     */
    public function resources(mixed ...$params): array
    {
        return ['echo://ponderings', 'echo://reverse/{text}'];
    }
}
