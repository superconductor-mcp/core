<?php

namespace Fabricate\Mechanism\Signals;

/**
 * Fired when the tools, prompts or resources a server offers change. Every MCPServer whose configuration declared
 * listChanged for that capability sends the matching list_changed notification. A session id limits it to that session.
 */
final readonly class ListChanged
{
    public const string TOOLS = 'tools';
    public const string PROMPTS = 'prompts';
    public const string RESOURCES = 'resources';

    public function __construct(
        public string $capability,
        public ?string $session_id = null,
    ) {}
}
