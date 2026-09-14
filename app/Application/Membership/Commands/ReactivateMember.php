<?php

namespace App\Application\Membership\Commands;

use Carbon\CarbonImmutable;

final readonly class ReactivateMember
{
    public function __construct(
        public string $memberId,
        public CarbonImmutable $reactivatedAt,
    ) {}
}
