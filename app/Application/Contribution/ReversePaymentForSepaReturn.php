<?php

namespace App\Application\Contribution;

use App\Domain\Contribution\Models\Payment;
use App\Domain\Sepa\Models\SepaDebitItem;
use App\Models\User;
use DomainException;

final readonly class ReversePaymentForSepaReturn
{
    public function __construct(
        private PaymentManager $paymentManager,
    ) {
    }

    public function handle(
        SepaDebitItem $item,
        string $reason,
        User $user,
    ): void {
        $payment =
            Payment::query()
                ->where(
                    'club_id',
                    $item->club_id
                )
                ->where(
                    'source_type',
                    'sepa_debit_item'
                )
                ->where(
                    'source_id',
                    $item->getKey()
                )
                ->first();

        if ($payment === null) {
            throw new DomainException(
                'Für diese SEPA-Position wurde keine Zahlung gefunden.'
            );
        }

        $this->paymentManager
            ->reverse(
                payment:
                $payment,

                reason:
                $reason,

                reversedBy:
                $user,
            );
    }
}
