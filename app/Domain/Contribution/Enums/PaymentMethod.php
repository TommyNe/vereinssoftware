<?php

namespace App\Domain\Contribution\Enums;

enum PaymentMethod: string
{
    case SepaDirectDebit = 'sepa_direct_debit';
    case BankTransfer = 'bank_transfer';
    case Cash = 'cash';
    case Card = 'card';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::SepaDirectDebit =>
            'SEPA-Lastschrift',

            self::BankTransfer =>
            'Überweisung',

            self::Cash =>
            'Barzahlung',

            self::Card =>
            'Kartenzahlung',

            self::Other =>
            'Sonstige',
        };
    }
}
