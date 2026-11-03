<?php

namespace Fabricate\Contracts\Schema;

enum ElicitAction: string
{
    case ACCEPT = 'accept';
    case DECLINE = 'decline';
    case CANCEL = 'cancel';
}
