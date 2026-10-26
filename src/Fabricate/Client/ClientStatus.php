<?php

namespace Fabricate\Client;

enum ClientStatus: string
{
    case NotConnected = 'not-connected';
    case Connecting = 'connecting';
    case Connected = 'connected';
    case Error = 'error';
    case Disabled = 'disabled';
}
