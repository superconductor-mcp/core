<?php

namespace Fabricate\NutsAndBolts;

// the RFC 5424 syslog severities MCP logging uses, least to most severe
enum LogLevel: string
{
    case Debug = 'debug';
    case Info = 'info';
    case Notice = 'notice';
    case Warning = 'warning';
    case Error = 'error';
    case Critical = 'critical';
    case Alert = 'alert';
    case Emergency = 'emergency';

    public function severity(): int
    {
        return array_search($this, self::cases(), true);
    }

    public function atLeast(LogLevel $minimum): bool
    {
        return $this->severity() >= $minimum->severity();
    }
}
