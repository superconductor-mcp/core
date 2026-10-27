<?php

return [
    'servers' => [
        'default_discovery_namespace' => "App\\Mcp\\Servers",
        'supported_mcp_versions' => ['2025-06-18', '2025-03-26', '2024-11-05']
    ],
    // servers this application connects to as a client, keyed by name; 'type' picks the transport
    // and defaults to 'stdio' when the entry has a 'command', so a Claude Desktop entry pastes in as-is
    'mcp_servers' => [],
    'client' => [
        // seconds a client may spend between opening its connection and finishing the handshake
        'connect_timeout' => 20,
        // Streamable HTTP: how many times in a row the listening GET stream is reopened after the server drops it without sending anything
        'stream_reconnects' => 5,
    ],
    'pagination' => [
        // how many entries one page of tools/list, resources/list or resources/templates/list holds
        'page_size' => 50,
    ],
    'logging' => [
        // the level a session starts at, until its client sends logging/setLevel
        'default_level' => 'info',
    ],
    'elicitation' => [
        // seconds a handler waits for the user's answer before it cancels the question
        'timeout' => 600,
    ],
    'progress' => [
        // the shortest gap, in seconds, between two progress notifications for one request
        'min_interval' => 0.1,
    ],
    'completion' => [
        // completion/complete calls a session may make per window before it is refused
        'rate_limit' => [
            'max' => 10,
            'per_seconds' => 1,
        ],
    ],
];