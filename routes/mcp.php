<?php

use Fabricate\Core\Routing\ProcedureRouterFacade as Route;
use Fabricate\Core\Rpc\Procedures\Autocomplete\CompletionProcedure;
use Fabricate\Core\Rpc\Procedures\Elicitation\ElicitationProcedure;
use Fabricate\Core\Rpc\Procedures\Logging\LoggingProcedure;
use Fabricate\Core\Rpc\Procedures\Ping\PingProcedure;
use Fabricate\Core\Rpc\Procedures\Prompts\PromptsProcedure;
use Fabricate\Core\Rpc\Procedures\Resources\ResourcesProcedure;
use Fabricate\Core\Rpc\Procedures\Resources\SubscriptionsProcedure;
use Fabricate\Core\Rpc\Procedures\Roots\RootsProcedure;
use Fabricate\Core\Rpc\Procedures\Sampling\SamplingProcedure;
use Fabricate\Core\Rpc\Procedures\Server\DiscoverProcedure;
use Fabricate\Core\Rpc\Procedures\Tasks\TasksProcedure;
use Fabricate\Core\Rpc\Procedures\Tools\ToolsProcedure;

Route::procedure('ping', PingProcedure::class);

Route::group('server', function () {
    Route::procedure('discover', DiscoverProcedure::class);
});

Route::group('prompts', function () {
    Route::procedure('list', PromptsProcedure::class.'@list');
    Route::procedure('get', PromptsProcedure::class.'@get');
});

Route::group('tools', function () {
    Route::procedure('list', ToolsProcedure::class.'@list');
    Route::procedure('call', ToolsProcedure::class.'@call');
});

Route::group('resources', function () {
    Route::procedure('list', ResourcesProcedure::class.'@list');
    Route::procedure('templates/list', ResourcesProcedure::class.'@listTemplates');
    Route::procedure('read', ResourcesProcedure::class.'@read');
    Route::procedure('subscribe', ResourcesProcedure::class.'@subscribe');
    Route::procedure('unsubscribe', ResourcesProcedure::class.'@unsubscribe');
});

Route::group('subscriptions', function () {
    Route::procedure('listen', SubscriptionsProcedure::class);
});

Route::group('completion', function () {
    Route::procedure('complete', CompletionProcedure::class);
});

Route::group('logging', function () {
    Route::procedure('setLevel', LoggingProcedure::class);
});

Route::group('tasks', function () {
    Route::procedure('get', TasksProcedure::class.'@get');
    Route::procedure('result', TasksProcedure::class.'@result');
    Route::procedure('list', TasksProcedure::class.'@list');
    Route::procedure('cancel', TasksProcedure::class.'@cancel');
    Route::procedure('update', TasksProcedure::class.'@update');
});

Route::group('sampling', function () {
    Route::procedure('createMessage', SamplingProcedure::class);
});

Route::group('roots', function () {
    Route::procedure('list', RootsProcedure::class);
});

Route::group('elicitation', function () {
    Route::procedure('create', ElicitationProcedure::class);
});
