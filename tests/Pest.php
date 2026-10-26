<?php

use Voyager\Config\Repository;
use Voyager\Vessel\ControlPanel;
use Voyager\IOPools\EventLoop;
use Voyager\IOPools\LoopWaiter;
use Voyager\IOPools\ResourceRegistry;
use Voyager\Contracts\IOPools\Loop;
use Voyager\IOPools\Waiter\StreamSelectWaiterBackend;
use Voyager\IOPools\PromiseEngines\GuzzlePromiseEngine;
use Fabricate\Client\MCPClient;
use Fabricate\Tests\Fixtures\FakeLog;
use Fabricate\Tests\Fixtures\FakeConnection;

function testLoop(int $pace_ms = 5): EventLoop
{
    $registry = new ResourceRegistry();

    return new EventLoop($registry, new LoopWaiter($registry, new StreamSelectWaiterBackend(), $pace_ms * 1_000_000), new GuzzlePromiseEngine());
}

// a container holding only what the package reads: the merged config, a log that records, and the loop
function bareApp(array $config = [], ?EventLoop $loop = null): ControlPanel
{
    $app = new ControlPanel();
    ControlPanel::setInstance($app);

    $app->registerInstance('config', new Repository(['mcp' => array_replace_recursive(require dirname(__DIR__).'/config/mcp.php', $config)]));
    $app->registerInstance('log', new FakeLog());
    $app->registerInstance(Loop::class, $loop ?? testLoop());

    return $app;
}

/** @return array{0: MCPClient, 1: FakeConnection} a client on a connection that records what it sends and takes replies by hand */
function fakeClient(array $config = [], ?EventLoop $loop = null, string $name = 'bench'): array
{
    $app = bareApp($config, $loop);
    $client = new MCPClient($name, $app->make(Loop::class));
    $connection = new FakeConnection($client);

    return [$client->attach($connection), $connection];
}

function log_entries(): array
{
    return app('log')->entries;
}
