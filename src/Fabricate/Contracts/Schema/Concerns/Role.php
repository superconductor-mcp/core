<?php

namespace Fabricate\Contracts\Schema\Concerns;

enum Role: string
{
    case USER = 'user';
    case ASSISTANT = 'assistant';
}
