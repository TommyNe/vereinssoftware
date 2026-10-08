<?php

namespace App\Application\Contribution;

use App\Domain\Contribution\Enums\ChargePaymentState;
use App\Domain\Contribution\Enums\PaymentStatus;
use App\Domain\Contribution\Models\ContributionCharge;
use App\Domain\Contribution\Models\Payment;

final class PaymentBalanceResolver
{
    public function allocatedAmount(
        ContributionCharge $charge,
    ): string {
        $amount =
            $charge
                ->paymentAllocations()
                ->whereHas(
                    'payment',
                    static function ($query): void {
                        $query->where(
                            'status',
                            PaymentStatus::Booked->value
                        );
                    }
                )
                ->sum('amount');

        return number_format(
            (float) $amount,
            2,
            '.',
            ''
        );
    }

    public function outstandingAmount(
        ContributionCharge $charge,
    ): string {
        $allocated =
            $this->allocatedAmount(
                $charge
            );

        $remaining =
            bcsub(
                (string) $charge->amount,
                $allocated,
                2
            );

        return bccomp(
            $remaining,
            '0.00',
            2
        ) < 0
            ? '0.00'
            : $remaining;
    }

    public function state(
        ContributionCharge $charge,
    ): ChargePaymentState {
        $allocated =
            $this->allocatedAmount(
                $charge
            );

        if (
            bccomp(
                $allocated,
                '0.00',
                2
            ) === 0
        ) {
            return ChargePaymentState::Open;
        }

        if (
            bccomp(
                $allocated,
                (string) $charge->amount,
                2
            ) >= 0
        ) {
            return ChargePaymentState::Paid;
        }

        return ChargePaymentState::PartiallyPaid;
    }

    public function unallocatedAmount(
        Payment $payment,
    ): string {
        $allocated =
            (string) $payment
                ->allocations()
                ->sum('amount');

        $remaining =
            bcsub(
                (string) $payment->amount,
                $allocated,
                2
            );

        return bccomp(
            $remaining,
            '0.00',
            2
        ) < 0
            ? '0.00'
            : $remaining;
    }
}
