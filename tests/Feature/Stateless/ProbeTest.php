<?php
use Fabricate\Core\Routing\ProcedureRouter;
use Fabricate\Mechanism\MessageDecoder;
use Fabricate\Server\MCPServer;
use Fabricate\Server\PromptRegistry;
use Fabricate\Server\ResourceRegistry;
use Fabricate\Server\ResourceTemplateRegistry;
use Fabricate\Server\ServerCapabilitiesFactory;
use Fabricate\Server\ToolRegistry;
use Voyager\Config\Repository;
use Voyager\Contracts\IOPools\Loop;
use Voyager\Signals\SignalDispatcher;
use Voyager\Vessel\ControlPanel;

it('probes', function () {
    ControlPanel::setInstance($panel = new ControlPanel());
    $panel->registerInstance('config', new Repository(['mcp' => require __DIR__.'/../../../config/mcp.php']));
    $panel->registerSingleton(ProcedureRouter::class);
    $panel->registerInstance('signals', new SignalDispatcher($panel));
    $panel->registerInstance(ToolRegistry::class, $tools = new ToolRegistry($panel));
    $tools->register(\App\Mcp\Tools\EchoTool::class);
    foreach([PromptRegistry::class, ResourceRegistry::class, ResourceTemplateRegistry::class] as $r) $panel->registerInstance($r, new $r($panel));
    $panel->registerInstance(Loop::class, $this->createMock(Loop::class));
    require __DIR__.'/../../../routes/mcp.php';

    $server = new MCPServer(new \App\Mcp\Servers\EchoServer(new ServerCapabilitiesFactory()), '2026-07-28');
    $meta = ['io.modelcontextprotocol/protocolVersion' => '2026-07-28', 'io.modelcontextprotocol/clientCapabilities' => (object) []];
    foreach([
        'no meta' => ['method' => 'tools/list'],
        'no caps' => ['method' => 'tools/list', 'params' => ['_meta' => ['io.modelcontextprotocol/protocolVersion' => '2026-07-28']]],
        'wrong version' => ['method' => 'tools/list', 'params' => ['_meta' => ['io.modelcontextprotocol/protocolVersion' => '2025-06-18', 'io.modelcontextprotocol/clientCapabilities' => (object) []]]],
        'tools/list' => ['method' => 'tools/list', 'params' => ['_meta' => $meta]],
        'ping' => ['method' => 'ping', 'params' => ['_meta' => $meta]],
        'setLevel' => ['method' => 'logging/setLevel', 'params' => ['level' => 'info', '_meta' => $meta]],
        'subscribe' => ['method' => 'resources/subscribe', 'params' => ['uri' => 'x://y', '_meta' => $meta]],
        'initialize' => ['method' => 'initialize', 'params' => ['protocolVersion' => '2026-07-28', 'capabilities' => (object) [], 'clientInfo' => ['name' => 'c', 'version' => '1'], '_meta' => $meta]],
        'discover' => ['method' => 'server/discover', 'params' => ['_meta' => $meta]],
        'call' => ['method' => 'tools/call', 'params' => ['name' => 'echo', 'arguments' => ['message' => 'hi'], '_meta' => $meta]],
        'tasks/get' => ['method' => 'tasks/get', 'params' => ['taskId' => 'x', '_meta' => $meta]],
    ] as $label => $msg) {
        try {
            $reply = $server->receiveMessage(MessageDecoder::fromWire(json_encode(['jsonrpc' => '2.0', 'id' => 1] + $msg)));
            fwrite(STDERR, str_pad($label, 14).($reply ? substr($reply->toJson(), 0, 260) : 'null')."\n");
        } catch (Throwable $e) { fwrite(STDERR, str_pad($label, 14).'THROWS '.get_class($e).': '.substr($e->getMessage(), 0, 200)."\n"); }
    }
    ControlPanel::setInstance(null);
    expect(true)->toBeTrue();
});
