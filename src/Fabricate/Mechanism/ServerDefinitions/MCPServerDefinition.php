<?php

namespace Fabricate\Mechanism\ServerDefinitions;

use Fabricate\Client\ClientConfiguration;
use Fabricate\Contracts\Client\ClientConnection;

/**
 * A server a client can connect to, listed in mcp.mcp_servers. Each transport has its own definition.
 */
abstract class MCPServerDefinition
{
    /**
     * Opens a connection to the server as the given client.
     */
    abstract public function connect(ClientConfiguration $client): ClientConnection;
}
