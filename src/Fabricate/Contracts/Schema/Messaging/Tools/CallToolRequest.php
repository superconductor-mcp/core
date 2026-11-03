<?php

namespace Fabricate\Contracts\Schema\Messaging\Tools;

use Fabricate\Contracts\Schema\Messaging\Request;

interface CallToolRequest extends Request
{
    /** The name of the tool to call. */
    public function name(): string;

    /** @return array<mixed>|null The arguments the tool is called with, by name; null when the request sends none. */
    public function arguments(): ?array;

    /** Whether the client asked for the call to run as a task (2025-11-25 task metadata). */
    public function runsAsTask(): bool;

    /** How long, in milliseconds, the client asked the task's result to be kept; null for the server's choice. */
    public function taskTtl(): ?int;
}
