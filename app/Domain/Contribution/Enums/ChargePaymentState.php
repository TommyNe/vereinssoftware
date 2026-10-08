<?php

namespace App\Domain\Contribution\Enums;

enum ChargePaymentState: string
{
    case Open = 'open';
    case PartiallyPaid = 'partially_paid';
    case Paid = 'paid';

    public function label(): string
    {
        return match ($this) {
            self::Open =>
            'Offen',

            self::PartiallyPaid =>
            'Teilweise bezahlt',

            self::Paid =>
            'Bezahlt',
        };
    }
}
