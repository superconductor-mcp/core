<?php

namespace Fabricate\Mechanism\Session;

use RuntimeException;

// an elicitation that produced no usable answer: not allowed here, refused, timed out, cancelled, or off-schema
final class ElicitationFailed extends RuntimeException
{
}
