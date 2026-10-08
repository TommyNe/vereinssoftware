<?php

namespace App\Application\Contribution;

use App\Domain\Contribution\Enums\ChargePaymentState;
use App\Domain\Contribution\Enums\ContributionChargeStatus;
use App\Domain\Contribution\Models\ContributionCharge;

final readonly class SyncContributionChargePaymentStatus
{
    public function __construct(
        private PaymentBalanceResolver $resolver,
    ) {
    }

    public function handle(
        ContributionCharge $charge,
    ): void {
        if (
            $charge->status
            === ContributionChargeStatus::Cancelled
        ) {
            return;
        }

        $state =
            $this->resolver
                ->state($charge);

        if (
            $state
            === ChargePaymentState::Paid
        ) {
            $charge->update([
                'status' =>
                    ContributionChargeStatus::Paid,

                'paid_at' =>
                    $charge->paid_at
                    ?? now(),
            ]);

            return;
        }

        /*
         * Partial bleibt auf Charge-Ebene
         * technisch "open".
         *
         * Der genaue Status wird über
         * PaymentBalanceResolver berechnet.
         */
        $charge->update([
            'status' =>
                ContributionChargeStatus::Open,

            'paid_at' =>
                null,
        ]);
    }
}
