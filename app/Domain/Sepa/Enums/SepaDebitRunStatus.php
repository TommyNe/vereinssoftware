<?php

namespace App\Domain\Sepa\Enums;

enum SepaDebitRunStatus: string
{
    case Draft = 'draft';
    case Prepared = 'prepared';
    case Exported = 'exported';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Entwurf',
            self::Prepared => 'Vorbereitet',
            self::Exported => 'Exportiert',
            self::Cancelled => 'Storniert',
        };
    }
}
