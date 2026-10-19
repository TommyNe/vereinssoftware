<?php

namespace App\Application\Contribution\Data;

final readonly class FinanceOverviewData
{
    /**
     * @param  array<int, ChargeFinanceData>  $openCharges
     * @param  array<int, PaymentFinanceData>  $unallocatedPayments
     */
    public function __construct(
        public string $totalOpen,
        public string $totalOverdue,
        public string $totalPartiallyPaid,
        public string $totalUnallocatedPayments,
        public int $openChargesCount,
        public int $overdueChargesCount,
        public int $partiallyPaidChargesCount,
        public int $unallocatedPaymentsCount,
        public array $openCharges,
        public array $unallocatedPayments,
    ) {}
}
