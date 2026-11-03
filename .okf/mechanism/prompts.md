---
type: Module
title: Prompts
description: How a server's prompts are discovered, registered, defined from their class, listed with prompts/list, filled with prompts/get and completed with completion/complete.
resource: src/Fabricate/Server/PromptRegistry.php
tags: [prompts, registry, discovery, pagination]
status: draft
generated: { by: claude-code/claude-opus-5-5, at: 2026-11-02T00:00:00Z }
sources:
  - id: prompt
    resource: src/Fabricate/Mechanism/Prompt.php
    title: Prompt
  - id: registry
    resource: src/Fabricate/Server/PromptRegistry.php
    title: PromptRegistry
  - id: procedure
    resource: src/Fabricate/Core/Rpc/Procedures/Prompts/PromptsProcedure.php
    title: PromptsProcedure
  - id: tests
    resource: tests/Feature/Prompts/PromptsTest.php
    title: Prompts tests
  - id: completion
    resource: src/Fabricate/Core/Rpc/Procedures/Autocomplete/CompletionProcedure.php
    title: CompletionProcedure
  - id: completion-tests
    resource: tests/Feature/Prompts/CompletionTest.php
    title: Completion tests
---

# Defining a prompt

A prompt extends `Mechanism\Prompt` and carries `#[PromptCall(name, description)]`. Its constructor is its argument list: each parameter is an argument, and one without a default is required. Every parameter must be a `string`, because prompt arguments are strings on the wire; `PromptRegistry` refuses any other type when the prompt is registered.[^prompt][^registry]

```php
#[PromptCall('greet', 'Ask for a greeting in the given tone.')]
class GreetPrompt extends Prompt
{
    public function __construct(public readonly string $name, public readonly string $tone = 'friendly') {}

    public function handle(): string
    {
        return "Write a {$this->tone} greeting for {$this->name}.";
    }
}
```

The wire object `prompts/list` returns is the version's `PromptDefinition`, built from the class.

# Metadata

`#[PromptCall]` takes a `title`, sent from 2025-06-18, and repeatable `#[Icon]`s are sent from 2025-11-25. An `#[Argument]` on a constructor parameter gives that argument its `description`, and from 2025-06-18 its `title`.

# Registry and discovery

`PromptRegistry`, bound as `mcp.prompts`, maps each `#[PromptCall]` name to its class; the provider registers every concrete prompt under `mcp.prompts.discovery_namespace`, `App\Mcp\Prompts` by default. A server offers the prompts whose names its `prompts()` returns.[^registry]

# prompts/list and prompts/get

`list` pages the server's prompt names like `tools/list`, with each argument's name and whether it is required. On 2026-07-28 the result is not cacheable.[^procedure]

`get` builds the prompt from the request's arguments by name and calls `handle()`, which gets the configuration and `$protocol_version` the same way a tool's does.[^procedure]

* An unknown prompt, and missing, unknown or positional arguments, are -32602 in every version. The message says why, without server paths.[^tests]
* `handle()` returns a string, the version's own `PromptMessage`, or a list of them. A string becomes a `user` text message; use a `PromptMessage` for the `assistant` role or other content. Anything else is -32603.[^tests]
* The result's description is the prompt's.
* A prompt can opt into async with `$async`, as a tool does; its answer comes by mail.

# completion/complete

A prompt suggests values for an argument by overriding the static `complete($argument, $value, $context)`, which returns a `Mechanism\CompletionResult`; the default suggests nothing. `$context` holds the arguments already filled in, which clients send from 2025-06-18.[^prompt][^completion]

* The server must declare the `completions` capability; without it the request is -32601.[^completion-tests]
* An unknown prompt, or an argument the prompt doesn't have, is -32602.
* A result holds at most 100 values; past that they are cut, `hasMore` is set, and `total` says how many exist when the prompt knows.
* `ref/resource` gets no suggestions until resource templates exist.

[^prompt]: Prompt
[^registry]: PromptRegistry
[^procedure]: PromptsProcedure
[^tests]: Prompts tests
[^completion]: CompletionProcedure
[^completion-tests]: Completion tests
