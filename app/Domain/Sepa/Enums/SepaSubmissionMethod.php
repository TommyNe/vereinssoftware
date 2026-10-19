<?php

namespace App\Domain\Sepa\Enums;

enum SepaSubmissionMethod: string
{
    case BankPortal = 'bank_portal';
    case OnlineBanking = 'online_banking';
    case Ebics = 'ebics';
    case Api = 'api';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::BankPortal => 'Bankportal',

            self::OnlineBanking => 'Onlinebanking',

            self::Ebics => 'EBICS',

            self::Api => 'Bank-API',

            self::Other => 'Sonstige',
        };
    }
}
