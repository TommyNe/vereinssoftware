<?php

namespace App\Domain\Contribution\Enums;

enum ContributionInterval: string
{
    case Once = 'once';
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';
    case HalfYearly = 'half_yearly';
    case Yearly = 'yearly';

    public function label(): string
    {
        return match ($this) {
            self::Once => 'Einmalig',
            self::Monthly => 'Monatlich',
            self::Quarterly => 'Vierteljährlich',
            self::HalfYearly => 'Halbjährlich',
            self::Yearly => 'Jährlich',
        };
    }
}
