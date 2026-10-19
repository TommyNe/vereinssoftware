<?php

namespace App\Application\Contribution;

use App\Domain\Contribution\Enums\ContributionChargeStatus;
use App\Domain\Contribution\Enums\PaymentStatus;
use App\Domain\Membership\Models\Member;

final readonly class MemberFinanceSummary
{
    public function __construct(
        private PaymentBalanceResolver $resolver,
    ) {}

    public function openAmount(
        Member $member,
    ): string {
        $total = '0.00';

        $charges =
            $member
                ->contributionCharges()
                ->where(
                    'status',
                    '!=',
                    ContributionChargeStatus::Cancelled->value
                )
                ->get();

        foreach ($charges as $charge) {
            $total =
                bcadd(
                    $total,
                    $this->resolver
                        ->outstandingAmount(
                            $charge
                        ),
                    2
                );
        }

        return $total;
    }

    public function creditAmount(
        Member $member,
    ): string {
        $total = '0.00';

        $payments =
            $member
                ->payments()
                ->where(
                    'status',
                    PaymentStatus::Booked->value
                )
                ->get();

        foreach ($payments as $payment) {
            $total =
                bcadd(
                    $total,
                    $this->resolver
                        ->unallocatedAmount(
                            $payment
                        ),
                    2
                );
        }

        return $total;
    }
}
