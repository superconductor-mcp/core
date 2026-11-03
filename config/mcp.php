<?php

return [
    'supported_protocol_versions' => [
        '2024-11-05',
        '2025-03-26',
        '2025-06-18',
        '2025-11-25',
        '2026-07-28'
    ],

    // Most messages a single JSON-RPC batch may carry.
    'max_batch_size' => 100,

    // Where the application keeps its server and client configurations; both must sit under the app namespace.
    'servers' => [
        'discovery_namespace' => 'App\\Mcp\\Servers',
    ],
    'clients' => [
        'discovery_namespace' => 'App\\Mcp\\Clients',
        // Seconds a client waits for the server to answer its handshake: server/discover, then initialize.
        'initialize_timeout' => 30,
    ],

    // The servers clients can connect to, by name: MCPServerDefinition objects from the transport packages.
    'mcp_servers' => [],
    'tools' => [
        'discovery_namespace' => 'App\\Mcp\\Tools',
    ],
    'prompts' => [
        'discovery_namespace' => 'App\\Mcp\\Prompts',
    ],
    // Resources and resource templates share one namespace.
    'resources' => [
        'discovery_namespace' => 'App\\Mcp\\Resources',
    ],

    // Seconds a request a server or client sends waits for its response before it is cancelled; a configuration's
    // $request_timeout overrides it. subscriptions/listen, which stays open until cancelled, never times out.
    'request_timeout' => 60,

    // Seconds a 2026-07-28 requestState stays valid for the retry it was issued for.
    'request_state' => [
        'ttl' => 300,
    ],

    // Most items a list request returns per page; the rest follow behind a cursor.
    'pagination' => [
        'page_size' => 50,
    ],
];
