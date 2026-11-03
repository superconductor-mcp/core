<?php

namespace Fabricate\Mechanism;

/**
 * What a procedure returns when its answer comes later: the work runs async and posts the answer back to the loop as mail.
 * The entity sends nothing for the request now.
 */
final readonly class Deferred
{
}
