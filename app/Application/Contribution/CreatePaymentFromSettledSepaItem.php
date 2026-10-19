<?php

namespace App\Application\Contribution;

use App\Application\Club\CurrentClub;
use App\Domain\Contribution\Enums\PaymentMethod;
use App\Domain\Contribution\Models\Payment;
use App\Domain\Sepa\Enums\SepaDebitItemStatus;
use App\Domain\Sepa\Models\SepaDebitItem;
use App\Models\User;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Support\Facades\DB;

final readonly class CreatePaymentFromSettledSepaItem
{
    public function __construct(
        private PaymentManager $paymentManager,
        private CurrentClub $currentClub,
    ) {}

    public function handle(
        SepaDebitItem $item,
        CarbonImmutable $bookingDate,
        ?User $recordedBy,
    ): Payment {
        if ((string) $item->club_id !== $this->currentClub->id()) {
            throw new DomainException('Die Lastschriftposition gehört nicht zum aktuellen Verein.');
        }

        if (
            $item->status
            !== SepaDebitItemStatus::Settled
        ) {
            throw new DomainException(
                'Nur ausgeführte SEPA-Positionen können als Zahlung gebucht werden.'
            );
        }

        return DB::transaction(
            function () use (
                $item,
                $bookingDate,
                $recordedBy,
            ): Payment {
                $existing =
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

                if ($existing !== null) {
                    return $existing;
                }

                $payment =
                    $this->paymentManager
                        ->create(
                            member: $item->member,

                            amount: (string) $item->amount,

                            method: PaymentMethod::SepaDirectDebit,

                            bookingDate: $bookingDate,

                            valueDate: $bookingDate,

                            reference: $item->end_to_end_id,

                            notes: null,

                            createdBy: $recordedBy
                            ?? throw new DomainException(
                                'Für manuell erfasste SEPA-Zahlungen muss ein Benutzer vorhanden sein.'
                            ),

                            sourceType: 'sepa_debit_item',

                            sourceId: (string) $item->getKey(),
                        );

                $this->paymentManager
                    ->allocate(
                        payment: $payment,

                        charge: $item->charge,

                        amount: (string) $item->amount,
                    );

                return $payment;
            }
        );
    }
}
