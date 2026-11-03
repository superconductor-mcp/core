<?php

namespace Fabricate\Contracts\Schema;

enum IncludeContext: string
{
    case NONE = 'none';
    case THIS_SERVER = 'thisServer';
    case ALL_SERVERS = 'allServers';
}
