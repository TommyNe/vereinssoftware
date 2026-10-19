<?php

namespace App\Application\Contribution;

use App\Application\Club\CurrentClub;
use App\Application\Contribution\Data\ChargeFinanceData;
use App\Application\Contribution\Data\FinanceOverviewData;
use App\Application\Contribution\Data\PaymentFinanceData;
use App\Domain\Contribution\Enums\ChargePaymentState;
use App\Domain\Contribution\Enums\ContributionChargeStatus;
use App\Domain\Contribution\Enums\PaymentStatus;
use App\Domain\Contribution\Models\ContributionCharge;
use App\Domain\Contribution\Models\Payment;
use Carbon\CarbonImmutable;

final readonly class FinanceOverviewService
{
    public function __construct(
        private CurrentClub $currentClub,
        private PaymentBalanceResolver $balanceResolver,
    ) {}

    public function getOverview(
        int $limit = 50,
    ): FinanceOverviewData {
        $clubId =
            $this->currentClub->id();

        $charges =
            ContributionCharge::query()
                ->where(
                    'club_id',
                    $clubId
                )
                ->where(
                    'status',
                    '!=',
                    ContributionChargeStatus::Cancelled->value
                )
                ->with([
                    'member',
                    'paymentAllocations.payment',
                ])
                ->orderBy('due_date')
                ->limit(500)
                ->get();

        $openCharges = [];

        $totalOpen = '0.00';
        $totalOverdue = '0.00';
        $totalPartiallyPaid = '0.00';

        $openCount = 0;
        $overdueCount = 0;
        $partialCount = 0;

        foreach ($charges as $charge) {
            $outstanding =
                $this->balanceResolver
                    ->outstandingAmount($charge);

            if (
                bccomp(
                    $outstanding,
                    '0.00',
                    2
                ) <= 0
            ) {
                continue;
            }

            $paid =
                $this->balanceResolver
                    ->allocatedAmount($charge);

            $state =
                $this->balanceResolver
                    ->state($charge);

            $overdue =
                $charge->due_date->isPast();

            $openCount++;

            $totalOpen =
                bcadd(
                    $totalOpen,
                    $outstanding,
                    2
                );

            if ($overdue) {
                $overdueCount++;

                $totalOverdue =
                    bcadd(
                        $totalOverdue,
                        $outstanding,
                        2
                    );
            }

            if (
                $state
                === ChargePaymentState::PartiallyPaid
            ) {
                $partialCount++;

                $totalPartiallyPaid =
                    bcadd(
                        $totalPartiallyPaid,
                        $outstanding,
                        2
                    );
            }

            if (
                count($openCharges)
                < $limit
            ) {
                $openCharges[] =
                    new ChargeFinanceData(
                        chargeId: (string) $charge->getKey(),

                        memberId: (string) $charge->member_id,

                        memberNumber: (string) $charge
                            ->member
                            ->member_number,

                        memberName: trim(
                            $charge->member->first_name
                            .' '
                            .$charge->member->last_name
                        ),

                        description: $charge->description,

                        amount: (string) $charge->amount,

                        paidAmount: $paid,

                        outstandingAmount: $outstanding,

                        paymentState: $state,

                        dueDate: CarbonImmutable::instance(
                            $charge->due_date
                        ),

                        overdue: $overdue,
                    );
            }
        }

        $paymentData =
            $this->unallocatedPayments(
                $clubId,
                $limit
            );

        $totalUnallocated =
            '0.00';

        foreach ($paymentData as $payment) {
            $totalUnallocated =
                bcadd(
                    $totalUnallocated,
                    $payment->unallocatedAmount,
                    2
                );
        }

        return new FinanceOverviewData(
            totalOpen: $totalOpen,

            totalOverdue: $totalOverdue,

            totalPartiallyPaid: $totalPartiallyPaid,

            totalUnallocatedPayments: $totalUnallocated,

            openChargesCount: $openCount,

            overdueChargesCount: $overdueCount,

            partiallyPaidChargesCount: $partialCount,

            unallocatedPaymentsCount: count($paymentData),

            openCharges: $openCharges,

            unallocatedPayments: $paymentData,
        );
    }

    /**
     * @return array<int, PaymentFinanceData>
     */
    private function unallocatedPayments(
        string $clubId,
        int $limit,
    ): array {
        $payments =
            Payment::query()
                ->where(
                    'club_id',
                    $clubId
                )
                ->where(
                    'status',
                    PaymentStatus::Booked->value
                )
                ->with([
                    'member',
                    'allocations',
                ])
                ->orderByDesc(
                    'booking_date'
                )
                ->limit(500)
                ->get();

        $result = [];

        foreach ($payments as $payment) {
            $unallocated =
                $this->balanceResolver
                    ->unallocatedAmount(
                        $payment
                    );

            if (
                bccomp(
                    $unallocated,
                    '0.00',
                    2
                ) <= 0
            ) {
                continue;
            }

            $allocated =
                bcsub(
                    (string) $payment->amount,
                    $unallocated,
                    2
                );

            $result[] =
                new PaymentFinanceData(
                    paymentId: (string) $payment->getKey(),

                    memberId: (string) $payment->member_id,

                    memberNumber: (string) $payment
                        ->member
                        ->member_number,

                    memberName: trim(
                        $payment->member->first_name
                        .' '
                        .$payment->member->last_name
                    ),

                    amount: (string) $payment->amount,

                    allocatedAmount: $allocated,

                    unallocatedAmount: $unallocated,

                    method: $payment->method,

                    status: $payment->status,

                    bookingDate: CarbonImmutable::instance(
                        $payment->booking_date
                    ),

                    reference: $payment->reference,
                );

            if (
                count($result)
                >= $limit
            ) {
                break;
            }
        }

        return $result;
    }
}
