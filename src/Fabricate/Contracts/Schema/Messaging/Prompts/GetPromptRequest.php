<?php

namespace Fabricate\Contracts\Schema\Messaging\Prompts;

use Fabricate\Contracts\Schema\Messaging\Request;

interface GetPromptRequest extends Request
{
    /** The name of the prompt to get. */
    public function name(): string;

    /** @return array<string, string>|null The arguments to fill the prompt with, by name; null when the request sends none. */
    public function arguments(): ?array;
}
