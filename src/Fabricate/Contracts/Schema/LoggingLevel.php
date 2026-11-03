<?php

namespace Fabricate\Contracts\Schema;

enum LoggingLevel: string
{
    case DEBUG = 'debug';
    case INFO = 'info';
    case NOTICE = 'notice';
    case WARNING = 'warning';
    case ERROR = 'error';
    case CRITICAL = 'critical';
    case ALERT = 'alert';
    case EMERGENCY = 'emergency';

    /**
     * Whether this level is as severe as another or more: the cases run from least to most severe.
     */
    public function atLeast(LoggingLevel $threshold): bool
    {
        return array_search($this, self::cases(), true) >= array_search($threshold, self::cases(), true);
    }
}
