<?php

namespace Fabricate\Mechanism\Concerns;

use Fabricate\Mechanism\Signals\ListChanged;

/**
 * Lets a tool, prompt or resource tell every session that the list it belongs to changed.
 * Each MCPServer whose configuration declared listChanged for the capability sends the notification.
 */
trait SignalsChanges
{
    /** The capability whose list a change to this concrete belongs to: tools, prompts or resources. */
    abstract protected static function listCapability(): string;

    /**
     * @param string|null $session_id Limits the notification to one session; every session when null.
     */
    public static function listChanged(?string $session_id = null): void
    {
        signal(new ListChanged(static::listCapability(), $session_id));
    }
}
