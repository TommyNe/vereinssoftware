<?php

namespace App\Domain\Sepa\Enums;

enum SepaDebitSubmissionStatus: string
{
    case Submitted = 'submitted';

    case Accepted = 'accepted';

    case PartiallyAccepted =
        'partially_accepted';

    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'Eingereicht',

            self::Accepted => 'Angenommen',

            self::PartiallyAccepted => 'Teilweise angenommen',

            self::Rejected => 'Abgelehnt',
        };
    }
}
