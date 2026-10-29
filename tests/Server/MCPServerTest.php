<?php

use Fabricate\Contracts\NutsAndBolts\LogLevel;
use Fabricate\NutsAndBolts\MCPServerBag;
use Fabricate\Tests\Fixtures\BenchServer;
use Fabricate\NutsAndBolts\Messages\Tools\ToolListChangedNotification;
use Fabricate\NutsAndBolts\Messages\Logging\LoggingMessageNotification;
use Fabricate\NutsAndBolts\Messages\Prompts\PromptListChangedNotification;
use Fabricate\NutsAndBolts\Messages\Resources\ResourceUpdatedNotification;
use Fabricate\NutsAndBolts\Messages\Resources\ResourcesListChangedNotification;

it('queues only the notifications its capabilities allow, and drains them once', function () {
    bareApp();
    $server = new BenchServer();

    $server->notify(new ToolListChangedNotification());          // tools.listChanged true
    $server->notify(new ResourcesListChangedNotification());     // resources.listChanged false
    $server->notify(new PromptListChangedNotification());        // no prompts capability
    $server->notify(new ResourceUpdatedNotification('x://y'));   // resources.subscribe true
    $server->log(LogLevel::Info, 'hello');                       // logging advertised

    $drained = $server->drainNotifications();
    expect(array_map(fn ($n) => $n::class, $drained))->toBe([ToolListChangedNotification::class, ResourceUpdatedNotification::class, LoggingMessageNotification::class]);
    expect($server->drainNotifications())->toBe([]);
});

it('filters what a session wants by its subscriptions and log level', function () {
    bareApp();
    $bag = new MCPServerBag();

    expect($bag->wants(new ResourceUpdatedNotification('x://y')))->toBeFalse();
    $bag->subscriptions['x://y'] = true;
    expect($bag->wants(new ResourceUpdatedNotification('x://y')))->toBeTrue();

    expect($bag->wants(new LoggingMessageNotification(LogLevel::Debug, 'd')))->toBeFalse();   // default level is info
    expect($bag->wants(new LoggingMessageNotification(LogLevel::Error, 'e')))->toBeTrue();

    $bag->log_level = LogLevel::Debug;
    expect($bag->wants(new LoggingMessageNotification(LogLevel::Debug, 'd')))->toBeTrue();
});
