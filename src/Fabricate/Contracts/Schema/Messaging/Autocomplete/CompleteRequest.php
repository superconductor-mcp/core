<?php

namespace Fabricate\Contracts\Schema\Messaging\Autocomplete;

use Fabricate\Contracts\Schema\Messaging\Request;

interface CompleteRequest extends Request
{
    /** What is being completed: ref/prompt or ref/resource. */
    public function referenceType(): string;

    /** The prompt's name for ref/prompt, the resource template's URI for ref/resource. */
    public function referenceName(): string;

    /** The argument being completed. */
    public function argumentName(): string;

    /** What has been typed for the argument so far. */
    public function argumentValue(): string;

    /** @return array<string, string> Arguments already filled in, by name; empty when the request sends none. */
    public function contextArguments(): array;
}
