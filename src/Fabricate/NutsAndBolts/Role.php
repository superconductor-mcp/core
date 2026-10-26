<?php

namespace Fabricate\NutsAndBolts;

// who speaks a prompt message
enum Role: string
{
    case User = 'user';
    case Assistant = 'assistant';
}
