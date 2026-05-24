<?php

namespace App\Domain\Incident;

enum IncidentSeverity: string
{
    case INFO = 'info';
    case WARNING = 'warning';
    case CRITICAL = 'critical';

    public function colorClass(): string
    {
        return match ($this) {
            self::INFO => 'bg-slate-100 text-slate-700',
            self::WARNING => 'bg-yellow-100 text-yellow-800',
            self::CRITICAL => 'bg-rose-100 text-rose-800',
        };
    }
}
