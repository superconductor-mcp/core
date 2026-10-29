# Security Policy

## Supported versions

superconductor/core is pre-1.0. No 0.x release receives security fixes or advisories; fixes land in the
next release line. Security support starts with 1.0.

| Version | Security fixes |
|---------|----------------|
| < 1.0   | No             |

## Reporting a vulnerability

Please don't open a public issue for a security problem.

Report it privately through GitHub: the **Report a vulnerability** button on the
[superconductor-mcp/core](https://github.com/superconductor-mcp/core) repository's **Security** tab.
If that isn't available, email **info@projectsaturnstudios.com**.

Include what you found, the affected version, the protocol version in play, the transport, and steps
to reproduce. Reports are read and weighed for the release line in development; before 1.0 there
is no response-time commitment.

## Security model

superconductor/core speaks the Model Context Protocol on both sides. A **server** runs the tools, resources
and prompts an application chooses to expose, for whichever client the transport connects. A **client**
runs the commands an application's `mcp.mcp_servers` list names, as child processes, and calls whatever
those servers offer. Each side trusts the application that configured it and nothing on the wire.

- **Every line is checked before it is routed.** The decoder rejects anything that is not a JSON-RPC 2.0
  object, any request without a string or integer id, any method it does not know, and any `params` whose
  shape does not match the method. A rejected line gets the JSON-RPC error the spec names, carrying the
  request's id when there is one; a malformed notification gets nothing.
- **Tools run with the application's privileges.** A tool's `handle()` is ordinary PHP inside the server
  process. Arguments arrive as the client sent them; the procedure checks only that the required ones are
  present and that no unknown ones are. Type and range checks are the tool's job, exactly as for any
  request handler. A tool that throws reports the failure in its result; it cannot take the server down.
- **Resources are served by uri, not by path.** A `resources/read` names a uri the server registered or a
  template it matches. Nothing maps a uri onto the filesystem unless a resource's own action does.
- **Ids cannot be replayed.** Each side keeps a ledger of the ids the other side has used in the session;
  a repeated id is refused without touching state.
- **The client spawns what it is told to.** An `mcp_servers` entry is a command, its arguments, its
  environment and its working directory. The client runs exactly that, with the application's own
  environment underneath the entry's. Treat the list as code: a writable config file is a writable
  command line. Servers it connects to are untrusted; their lists, results and notifications are data.
- **Server pings are rate-bound.** A client that stops answering is dropped after `stdio.ping_time.max_misses`
  unanswered pings; a handshake that never completes is dropped after `stdio.handshake.stop_waiting.ttl`
  seconds; a client that waits on a server gives up after `mcp.client.connect_timeout` seconds.
- **Completion is rate-limited** per session (`mcp.completion.rate_limit`), and list responses are
  paginated (`mcp.pagination.page_size`), so neither can be used to make the server do unbounded work
  on one request.

A report is in scope when the package itself misbehaves on well-formed or malformed input: a line that
bypasses the decoder's checks, a request that reaches a procedure the lifecycle state should have refused,
a notification that leaks to a client whose capabilities or subscriptions should have filtered it, an id
replay that is accepted. What a tool, resource or prompt does once called is the application's code.
