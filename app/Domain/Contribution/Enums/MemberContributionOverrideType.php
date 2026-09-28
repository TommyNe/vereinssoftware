<?php

namespace App\Domain\Contribution\Enums;

enum MemberContributionOverrideType: string
{
    case FixedAmount = 'fixed_amount';
    case Exempt = 'exempt';

    public function label(): string
    {
        return match ($this) {
            self::FixedAmount => 'Individueller Betrag',

            self::Exempt => 'Beitragsbefreit',
        };
    }
}
