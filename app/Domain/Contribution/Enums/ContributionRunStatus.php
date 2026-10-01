<?php

namespace App\Domain\Contribution\Enums;

enum ContributionRunStatus: string
{
    case Pending = 'pending';
    case Running = 'running';
    case Completed = 'completed';
    case CompletedWithErrors = 'completed_with_errors';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Ausstehend',

            self::Running => 'Wird verarbeitet',

            self::Completed => 'Abgeschlossen',

            self::CompletedWithErrors => 'Mit Fehlern abgeschlossen',

            self::Failed => 'Fehlgeschlagen',
        };
    }
}
