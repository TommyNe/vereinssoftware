<?php

namespace App\Domain\Membership\Enums;

enum MemberDocumentType: string
{
    case MembershipApplication =
    'membership_application';

    case SepaMandate =
    'sepa_mandate';

    case Consent =
    'consent';

    case Certificate =
    'certificate';

    case Other =
    'other';

    public function label(): string
    {
        return match ($this) {
            self::MembershipApplication =>
            'Mitgliedsantrag',

            self::SepaMandate =>
            'SEPA-Mandat',

            self::Consent =>
            'Einwilligung',

            self::Certificate =>
            'Bescheinigung',

            self::Other =>
            'Sonstiges',
        };
    }
}
