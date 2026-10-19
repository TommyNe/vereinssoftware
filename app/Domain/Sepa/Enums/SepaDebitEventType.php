<?php

namespace App\Domain\Sepa\Enums;

enum SepaDebitEventType: string
{
    case Submitted = 'submitted';

    case Accepted = 'accepted';

    case Rejected = 'rejected';

    case Settled = 'settled';

    case Returned = 'returned';

    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'Eingereicht',

            self::Accepted => 'Angenommen',

            self::Rejected => 'Abgelehnt',

            self::Settled => 'Ausgeführt',

            self::Returned => 'Rücklastschrift',

            self::Refunded => 'Erstattung',
        };
    }
}
