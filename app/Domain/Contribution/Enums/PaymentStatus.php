<?php

namespace App\Domain\Contribution\Enums;

enum PaymentStatus: string
{
    case Booked = 'booked';
    case Reversed = 'reversed';

    public function label(): string
    {
        return match ($this) {
            self::Booked =>
            'Gebucht',

            self::Reversed =>
            'Storniert / zurückgebucht',
        };
    }
}
