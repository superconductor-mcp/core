<?php

namespace Fabricate\Contracts\Server;

use Fabricate\Contracts\Mechanism\EntityConfiguration;
use Fabricate\Contracts\Schema\LoggingLevel;
use Fabricate\Server\Tasks\TaskStore;
use Fabricate\Contracts\Schema\Messaging\Initialize\InitializeResult;

interface ServerConfiguration extends EntityConfiguration
{
    public function initializeResult(string $requested_protocol_version): InitializeResult;

    public function logLevel(): ?LoggingLevel;

    public function setLogLevel(LoggingLevel $level): void;

    public function subscribe(string $uri): void;

    public function unsubscribe(string $uri): void;

    public function subscribedTo(string $uri): bool;

    public function taskStore(): TaskStore;

    public function announcesListChanges(string $capability): bool;

    /** How to use the server, sent with initialize and server/discover. */
    public function instructions(): ?string;
}
