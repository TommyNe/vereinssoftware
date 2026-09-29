<?php

namespace App\Domain\Contribution\Enums;

enum ContributionChargeStatus: string
{
    case Open = 'open';
    case Paid = 'paid';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Offen',
            self::Paid => 'Bezahlt',
            self::Cancelled => 'Storniert',
        };
    }
}
