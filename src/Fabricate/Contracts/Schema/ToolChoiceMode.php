<?php

namespace Fabricate\Contracts\Schema;

enum ToolChoiceMode: string
{
    case AUTO = 'auto';
    case REQUIRED = 'required';
    case NONE = 'none';
}
