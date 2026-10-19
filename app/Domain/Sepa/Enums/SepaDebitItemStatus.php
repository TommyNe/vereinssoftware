<?php

namespace App\Domain\Sepa\Enums;

enum SepaDebitItemStatus: string
{
    case Prepared = 'prepared';

    case Submitted = 'submitted';

    case Accepted = 'accepted';

    case Rejected = 'rejected';

    case Settled = 'settled';

    case Returned = 'returned';

    case Refunded = 'refunded';

    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Prepared => 'Vorbereitet',

            self::Submitted => 'Eingereicht',

            self::Accepted => 'Angenommen',

            self::Rejected => 'Abgelehnt',

            self::Settled => 'Ausgeführt',

            self::Returned => 'Rücklastschrift',

            self::Refunded => 'Erstattet',

            self::Cancelled => 'Storniert',
        };
    }
}
