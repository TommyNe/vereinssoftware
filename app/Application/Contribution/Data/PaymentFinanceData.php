<?php

namespace App\Application\Contribution\Data;

use App\Domain\Contribution\Enums\PaymentMethod;
use App\Domain\Contribution\Enums\PaymentStatus;
use Carbon\CarbonImmutable;

final readonly class PaymentFinanceData
{
    public function __construct(
        public string $paymentId,
        public string $memberId,
        public string $memberNumber,
        public string $memberName,
        public string $amount,
        public string $allocatedAmount,
        public string $unallocatedAmount,
        public PaymentMethod $method,
        public PaymentStatus $status,
        public CarbonImmutable $bookingDate,
        public ?string $reference,
    ) {}
}
