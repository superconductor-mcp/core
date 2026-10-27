<?php

namespace Fabricate\NutsAndBolts\Messages\Elicitation;

// how the user answered: submitted, said no, or dismissed without choosing
enum ElicitAction: string
{
    case Accept = 'accept';
    case Decline = 'decline';
    case Cancel = 'cancel';
}
