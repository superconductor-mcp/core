---
type: Module
title: Resources
description: How a server's resources and resource templates are defined, discovered, listed, read, completed and subscribed to.
resource: src/Fabricate/Core/Rpc/Procedures/Resources/ResourcesProcedure.php
tags: [resources, templates, subscriptions, registry]
status: draft
generated: { by: claude-code/claude-opus-5-5, at: 2026-11-02T00:00:00Z }
sources:
  - id: resource
    resource: src/Fabricate/Mechanism/Resource.php
    title: Resource
  - id: template
    resource: src/Fabricate/Mechanism/ResourceTemplate.php
    title: ResourceTemplate
  - id: procedure
    resource: src/Fabricate/Core/Rpc/Procedures/Resources/ResourcesProcedure.php
    title: ResourcesProcedure
  - id: tests
    resource: tests/Feature/Resources/ResourcesTest.php
    title: Resources tests
---

# Defining resources

A resource at one URI extends `Mechanism\Resource` and carries `#[ResourceCall(uri, name, description, mimeType)]`; a template extends `Mechanism\ResourceTemplate` with `#[ResourceTemplateCall(uriTemplate, ...)]`. Each has a `read()` method called through the container: it can ask for `$uri`, a template's variables by name, the server configuration and `$protocol_version`. It returns a string (the resource's text, with the declared MIME type), the version's own contents objects, or a list of them.[^resource][^template]

Templates use RFC 6570 simple expansion: `{name}` fills one path segment, `{+name}` may span several; values are URL-decoded. A template overrides the static `complete()` to suggest values for its variables.[^template]

The wire objects the list methods return are the version's `ResourceDefinition` and `ResourceTemplateDefinition`.

# Metadata

`#[ResourceCall]` takes a `title` and the resource's `size` in bytes; `#[ResourceTemplateCall]` takes a `title`. `#[ResourceAnnotations]` gives `audience`, `priority` and `lastModified`. Each version gets what it has room for: `size` and audience and priority in every version, `title` from 2025-06-18, `lastModified` and repeatable `#[Icon]`s from 2025-11-25.[^resource][^template]

# Registries and discovery

`ResourceRegistry` keys resources by URI and `ResourceTemplateRegistry` keys templates by URI template. The provider registers both kinds found under `mcp.resources.discovery_namespace`, `App\Mcp\Resources` by default. A server offers the URIs and URI templates its `resources()` returns; one in neither registry is -32603.[^procedure]

# Calls

* `resources/list` and `resources/templates/list` page the offered resources and templates apart. On 2026-07-28 neither result is cacheable.[^tests]
* `resources/read` reads the resource with that exact URI, or else the first offered template the URI fits. A URI nothing reads is -32002 with `data.uri` through 2025-11-25, and -32602 on 2026-07-28, which made that code a MUST. Subscribing to one is refused the same way.[^procedure]
* `completion/complete` with `ref/resource` asks the template's `complete()`; an unknown template or variable is -32602.
* `resources/subscribe` records a URI the server can read on the configuration, and `resources/unsubscribe` forgets it. Up to 2025-11-25, on servers declaring `resources.subscribe`.

# Changes

`Resource::updated()` and `ResourceTemplate::updated($uri)` fire `ResourceUpdated`; every session subscribed to that URI gets `notifications/resources/updated`. `listChanged()` on any resource, prompt or tool fires `ListChanged`. See [Entities](/mechanism/entities.md).[^tests]

A resource can opt into async with `$async`, as a tool does; its answer comes by mail.

[^resource]: Resource
[^template]: ResourceTemplate
[^procedure]: ResourcesProcedure
[^tests]: Resources tests
