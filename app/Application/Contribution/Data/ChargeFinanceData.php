<?php

namespace App\Application\Contribution\Data;

use App\Domain\Contribution\Enums\ChargePaymentState;
use Carbon\CarbonImmutable;

final readonly class ChargeFinanceData
{
    public function __construct(
        public string $chargeId,
        public string $memberId,
        public string $memberNumber,
        public string $memberName,
        public string $description,
        public string $amount,
        public string $paidAmount,
        public string $outstandingAmount,
        public ChargePaymentState $paymentState,
        public CarbonImmutable $dueDate,
        public bool $overdue,
    ) {}
}
