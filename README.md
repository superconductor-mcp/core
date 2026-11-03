# Superconductor MCP

[![Tests](https://github.com/superconductor-mcp/core/actions/workflows/tests.yml/badge.svg)](https://github.com/superconductor-mcp/core/actions/workflows/tests.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/superconductor/core.svg)](https://packagist.org/packages/superconductor/core)
[![Total Downloads](https://img.shields.io/packagist/dt/superconductor/core.svg)](https://packagist.org/packages/superconductor/core)
[![License](https://img.shields.io/packagist/l/superconductor/core.svg)](LICENSE)

![Superconductor](superconductor.jpg)

## About Superconductor MCP

> **Note:** This repository contains the core of Superconductor MCP. If you want to build an application using Venusian, visit the main [Venusian skeleton repository](https://github.com/VenusianPHP/venusian).

Superconductor MCP brings the Model Context Protocol to your Venusian application: MCP servers and MCP clients, with tools, prompts, resources and tasks, all in the Laravel-like syntax you already know.

Want an AI assistant to drive the robot arm your app already controls? Write a tool class, name it on a server, and the assistant can call it. Want your app to call out to someone else's MCP server? Configure a client, connect, and send it requests from your own event loop.

Every MCP version is here, 2024-11-05 through 2026-07-28, each its own hand-written schema. A server answers in whichever version the client speaks, and a client settles on the newest one both sides speak, so you write a tool once and it works for every client.

Core holds the protocol. The transports are their own packages: [superconductor/stdio](https://github.com/superconductor-mcp/stdio) runs servers and connects clients over stdio, and [superconductor/web](https://github.com/superconductor-mcp/web) does it over HTTP+SSE and Streamable HTTP.

> **Built with AI assistance.** This package was developed LLM-assisted — not vibe-coded. Generated code was deliberately prompted and reviewed by a professional software engineer. See [AI-assisted development](#ai-assisted-development).

## Learning Superconductor MCP

Superconductor MCP has documentation and guides on the [Venusian website](https://venusian.projectsaturnstudios.com/docs/superconductor/overview), from [installing it](https://venusian.projectsaturnstudios.com/docs/superconductor/installation) and [writing servers](https://venusian.projectsaturnstudios.com/docs/superconductor/servers) to [tools](https://venusian.projectsaturnstudios.com/docs/superconductor/tools) and [connecting clients](https://venusian.projectsaturnstudios.com/docs/superconductor/clients).

## AI-assisted development

This codebase was built with LLM assistance under human direction. Prompts, reviews, and architectural decisions were owned by a professional software engineer — the model accelerates drafting and exploration; it does not replace judgment on APIs, dependency direction, or what ships.

## Contributing

Thank you for considering contributing to Superconductor MCP! The contribution guide can be found in the [Venusian documentation](https://venusian.projectsaturnstudios.com/docs/contributions).

## Code of Conduct

In order to ensure that the Venusian community is welcoming to all, please review and abide by the [Code of Conduct](https://venusian.projectsaturnstudios.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

Please review [our security policy](https://github.com/superconductor-mcp/core/security/policy) on how to report security vulnerabilities.

## License

Superconductor MCP is open-sourced software licensed under the [MIT license](LICENSE).
