<div align="center" id="header">

<h1>superconductor/mcp</h1>
<h3>The Model Context Protocol as a VenusianPHP module: serve it, consume it, same event loop</h3>

[![CI](https://img.shields.io/github/actions/workflow/status/superconductor-mcp/mcp/tests.yml?style=flat-square&label=CI)](https://github.com/superconductor-mcp/mcp/actions/workflows/tests.yml)
[![Packagist](https://img.shields.io/packagist/v/superconductor/mcp?style=flat-square)](https://packagist.org/packages/superconductor/mcp)
[![PHP](https://img.shields.io/packagist/dependency-v/superconductor/mcp/php?style=flat-square&label=PHP)](https://packagist.org/packages/superconductor/mcp)
[![MCP](https://img.shields.io/badge/MCP-2024--11--05-5b4ee6?style=flat-square)](https://modelcontextprotocol.io/specification/2024-11-05)
[![License](https://img.shields.io/github/license/superconductor-mcp/mcp?style=flat-square)](LICENSE)
[![OKF](https://img.shields.io/badge/OKF-0.2-6f42c1?style=flat-square&label=knowledge%20bundle)](.okf/index.md)

</div>

The Model Context Protocol is how an LLM harness talks to the tools, documents and prompts an application wants to offer it: JSON-RPC 2.0 messages with a fixed vocabulary, over a transport the two sides agree on. Every SDK for it gives you a server class, a client class, and an event loop of its own to run them on.

A VenusianPHP application already has an event loop. Its sketches run on it, its worker pools run on it, its sockets and timers wake on it. <b>The only problem?</b> An MCP SDK brings its own loop and wants the application to wait on it.

superconductor/mcp speaks MCP as a native citizen of that loop. A **server** is an `MCPServer` class that lists what it offers, run as a sketch by a transport package. A **client** is an `MCPClient` the application holds inside its own loop, one per connection, built from an `mcp_servers` list in the same shape Claude Desktop uses. Neither side owns a loop; both ride the one the application already has.

## Basic usage

Most ways you will interface with superconductor/mcp are in two parts -

**Serving.** A server is a class with a `#[Server]` name, its capabilities, and what it offers. Each tool, resource or prompt is a definition pointing at a class with `handle()`.

```php
#[Server('concept')]
class ConceptServer extends MCPServer
{
    protected array $entity_info = ['name' => 'Concept', 'version' => '1.0.0'];

    protected array $entity_capabilities = [
        'tools' => ['listChanged' => true],
        'resources' => ['subscribe' => true, 'listChanged' => false],
        'prompts' => ['listChanged' => false],
        'logging' => [],
    ];

    public function tools(): array
    {
        return [
            new ToolDefinition('echo', 'You get out what you put in', ['input' => new StringProperty('What to send back')], ['input'], action: EchoTool::class),
        ];
    }
}

#[ToolCall]
class EchoTool
{
    public function handle(string $input): TextProperty
    {
        return new TextProperty($input);
    }
}
```

```bash
php rocket mcp:cli concept      # with semiconductor/stdio installed
```

**Consuming.** Name the servers in `config/mcp.php`, then take the manager out of the container. Every method that talks to a server returns an IOPools promise.

```php
'mcp_servers' => [
    'concept' => ['type' => 'stdio', 'command' => PHP_BINARY, 'args' => ['rocket', 'mcp:cli', 'concept'], 'cwd' => base_path()],
    'neo4j'   => ['type' => 'stdio', 'command' => 'neo4j-mcp', 'env' => ['NEO4J_URI' => env('NEO4J_URI')]],
],
```

```php
$clients = app(ClientManager::class);           // or app('mcp.clients')

foreach ($clients->connectAll() as $name => $client) {
    $client->ready()->wait();                   // handshake done, every list cached
    $client->tools();                           // the server's tools, every page
}

$result = $clients->client('neo4j')
    ->callTool('read-cypher', ['query' => 'MATCH (n) RETURN count(n) AS nodes'])
    ->wait();

$result->content;                               // [['type' => 'text', 'text' => '…']]
$clients->tools();                              // every server's tools as {server}__{tool}
$clients->closeAll();                           // each server shut down in the spec's order
```

## Requirements

| | |
|---|---|
| PHP | 8.4 or newer. |
| VenusianPHP | A 0.10 application: `venusian-voyager/console`, `contracts`, `io-pools`, `nuts-and-bolts` and `workflows`. |
| A transport | One `semiconductor/*` package. Today that is [semiconductor/stdio](https://github.com/superconductor-mcp/stdio). |
| Protocol | 2024-11-05. Later stops on the line (2025-03-26 onward) are added one at a time and never drop an earlier one. |

## Installation

```bash
composer require superconductor/mcp semiconductor/stdio
```

The service provider is discovered. It merges `config/mcp.php`, discovers servers under `App\Mcp\Servers`, binds the server repository as `mcp.servers` and the client manager as `mcp.clients`. Publish the config to change any of it:

```bash
php computer vendor:publish --tag=mcp-config
```

Verify with the transport's own commands; with semiconductor/stdio:

```console
$ php rocket list | grep mcp:
  mcp:cli      Start an STDIO-driven MCP Server

$ printf '%s\n' '{"jsonrpc":"2.0","id":0,"method":"initialize","params":{"protocolVersion":"2024-11-05","capabilities":{},"clientInfo":{"name":"me","version":"1"}}}' | php rocket mcp:cli concept
{"jsonrpc":"2.0","id":0,"result":{"protocolVersion":"2024-11-05","capabilities":{"tools":{"listChanged":true},…},"serverInfo":{"name":"Concept","version":"1.0.0"}}}
```

## Configuration

`config/mcp.php`:

| Key | Default | What it does |
|---|---|---|
| `servers.default_discovery_namespace` | `App\Mcp\Servers` | Where `#[Server]` classes are found. Must sit under the application namespace. |
| `servers.supported_mcp_versions` | `['2024-11-05']` | What a server will speak and what a client proposes (the latest). |
| `mcp_servers` | `[]` | The servers this application connects to, keyed by name: `type`, `command`, `args`, `env`, `cwd`, `disabled`. An entry with a `command` and no `type` is `stdio`, so a Claude Desktop entry pastes in as it is. |
| `client.connect_timeout` | `20` | Seconds a client may spend between opening its connection and finishing the handshake. |
| `pagination.page_size` | `50` | Entries per page of `tools/list`, `resources/list`, `resources/templates/list` and `prompts/list`. |
| `logging.default_level` | `info` | The level a session starts at until its client sends `logging/setLevel`. |
| `progress.min_interval` | `0.1` | Shortest gap, in seconds, between two progress notifications for one request. |
| `completion.rate_limit` | `10` per `1` s | `completion/complete` calls a session may make per window. |

## Serving

Everything a server offers is a definition. A definition's `action` is a closure or a class carrying the matching attribute and a public `handle()`; the request's arguments are injected by name.

| Offer | Definition | Action attribute | `handle()` returns |
|---|---|---|---|
| Tool | `ToolDefinition(name, description, properties, required, action)` | `#[ToolCall]` | A `SchemaPrimitive` or a list of them: `TextProperty`, `ImageProperty`, `EmbeddedResourceProperty`. Throwing reports `isError` in the result. |
| Resource | `ResourceDefinition(uri, name, description, mimeType, action)` | `#[ResourceRead]` | `ResourceContents(uri, mimeType, text or blob)`. |
| Resource template | `ResourceTemplateDefinition(uriTemplate, name, description, mimeType, action, completers)` | `#[ResourceRead]` | `ResourceContents`; the `{variables}` of the template arrive as arguments. |
| Prompt | `PromptDefinition(name, description, arguments, action)` with `PromptArgument(name, description, required, completer)` | `#[PromptGet]` | A list of `PromptMessage::user(…)` / `PromptMessage::assistant(…)`. |
| Completion | A `completer` on a `PromptArgument` or template variable | `#[Completes]` | `CompletionResult` (at most 100 values, `hasMore`). |

Override `tools()`, `resources()`, `resourceTemplates()` or `prompts()` to compute the list per call. What changes at runtime is announced through the server itself:

```php
$this->notify(new ToolListChangedNotification());       // sent only if tools.listChanged is advertised
$this->notify(new ResourceUpdatedNotification($uri));   // reaches only sessions subscribed to $uri
$this->log(LogLevel::Info, 'document.flipped', ['visible' => false]);   // filtered by each session's level
```

A tool that runs long takes a `ProgressReporter` in its constructor and calls `report($progress, $total)`; the notifications go out while it runs. Tool calls, resource reads, prompt gets and completion run in a loop fiber, so a tool that waits on the loop (`$loop->await(…)`, `$loop->until(…)`) keeps the server answering pings and honouring `notifications/cancelled` meanwhile.

## Consuming

`ClientManager` (`mcp.clients`) is a `MultipleInstanceManager`: one `MCPClient` per `mcp_servers` entry, built by the transport the entry's `type` names.

| Call | Gives |
|---|---|
| `connectAll()` | Every client, each connecting at once. Read `ready()` or `status()` per client. |
| `client($name)` | One client, connected on first use. |
| `tools()` | Every connected client's tools as `{server}__{tool}`. |
| `callTool('{server}__{tool}', $args)` | The call routed to the client that owns the tool. |
| `closeAll()` | Every connection closing side by side; returns once each has ended. |

`MCPClient`:

| Call | Gives |
|---|---|
| `status()` | `NotConnected`, `Connecting`, `Connected`, `Error` or `Disabled`. `lastError()` says why for `Error`. |
| `ready()` | A promise resolving with the client once the handshake is done and every list the server advertises is cached; rejected with `RequestFailed` when connecting failed. |
| `serverInfo()`, `serverCapabilities()`, `protocolVersion()` | What the handshake negotiated. |
| `tools()`, `resources()`, `resourceTemplates()`, `prompts()` | The caches, every page, refilled when the server sends a `list_changed`. |
| `ping()`, `listTools($cursor)`, `callTool($name, $args)`, `listResources($cursor)`, `listResourceTemplates($cursor)`, `readResource($uri)`, `listPrompts($cursor)`, `getPrompt($name, $args)` | A promise of the typed response. A JSON-RPC error reply rejects it with `RequestFailed` carrying `rpc_code` and `data`. |
| `request(MCPRequest, $responseClass)` | The same for any request class. |
| `close()` | Rejects what is pending and ends the connection. |

The client answers the server's pings, refuses a server id it has already seen, and logs the server's `notifications/message` lines into the application log at the level they were sent.

## Packages you might use with this one

| Package | Gives |
|---|---|
| [semiconductor/stdio](https://github.com/superconductor-mcp/stdio) | The stdio transport: `rocket mcp:cli <server>` to serve, `StdioConnection` to consume. |

## AI-assisted development

This codebase was built with LLM assistance under human direction. Prompts, reviews, and architectural decisions were owned by a professional software engineer — the model accelerates drafting and exploration; it does not replace judgment on APIs, dependency direction, or what ships.

## Agentic Documentation

This repository carries an [Open Knowledge Format](https://github.com/GoogleCloudPlatform/knowledge-catalog/blob/main/okf/SPEC.md) bundle at [`.okf/`](.okf/): Markdown with YAML frontmatter, readable by you and loadable by a coding agent. It records what the README doesn't: why server and client are different entities on one loop, how a line becomes a typed message and how an error keeps its id, why each side keeps two id ledgers, which procedures run in a fiber and what that buys, and a log of every change that altered behaviour.

Start at [`.okf/index.md`](.okf/index.md). [`AGENTS.md`](AGENTS.md) is the short rule sheet an agent reads before touching a message, a procedure, the client or the server; each rule points into the bundle for the reason behind it.

Point your agent at the repo and it has the same context the maintainer has. Point yourself at it when the README's answer is "it does X" and you want "because".

## Resources

* [MCP specification 2024-11-05](https://modelcontextprotocol.io/specification/2024-11-05): the wire format this version serves and consumes.
* [JSON-RPC 2.0](https://www.jsonrpc.org/specification): the envelope every message rides in.
* [VenusianPHP](https://github.com/VenusianPHP): the framework, its sketches and the IOPools loop this module rides.
* [Open Knowledge Format](https://github.com/GoogleCloudPlatform/knowledge-catalog/blob/main/okf/SPEC.md): the spec the [`.okf/`](.okf/) bundle follows.

## Running the test suite

The suite is Pest v4 and needs no server: the client runs on a fake connection that records what it sends and takes replies by hand, procedures run against a bench `MCPServer`, the decoder against lines.

```bash
git clone https://github.com/superconductor-mcp/mcp.git
cd mcp
composer install
vendor/bin/pest
```

```console
Tests:    39 passed (159 assertions)
```

## Contributing

Open an issue first for anything beyond a typo. Pull requests come with a Pest test for the behaviour and keep CI green. A change to a wire shape names the spec page it follows. Read [`AGENTS.md`](AGENTS.md) for the rules the code follows.

## Security

A server runs the application's own tools for whichever client the transport connects; a client spawns the commands its config names. See [SECURITY.md](SECURITY.md) for the model and how to report a vulnerability.

## License

MIT. See [LICENSE](LICENSE).
