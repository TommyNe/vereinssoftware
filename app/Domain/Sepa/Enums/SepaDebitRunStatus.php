<?php

namespace App\Domain\Sepa\Enums;

enum SepaDebitRunStatus: string
{
    case Draft = 'draft';
    case Prepared = 'prepared';
    case Exported = 'exported';

    case Submitted = 'submitted';

    case Accepted = 'accepted';

    case PartiallyAccepted =
        'partially_accepted';

    case Rejected = 'rejected';

    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Entwurf',

            self::Prepared => 'Vorbereitet',

            self::Exported => 'Exportiert',

            self::Submitted => 'Bei Bank eingereicht',

            self::Accepted => 'Von Bank angenommen',

            self::PartiallyAccepted => 'Teilweise angenommen',

            self::Rejected => 'Von Bank abgelehnt',

            self::Cancelled => 'Storniert',
        };
    }
}
