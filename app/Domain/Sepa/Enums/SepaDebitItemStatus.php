<?php

namespace App\Domain\Sepa\Enums;

enum SepaDebitItemStatus: string
{
    case Prepared = 'prepared';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Prepared => 'Vorbereitet',
            self::Cancelled => 'Storniert',
        };
    }
}
