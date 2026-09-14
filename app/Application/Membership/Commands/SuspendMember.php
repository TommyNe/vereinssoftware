<?php

namespace App\Application\Membership\Commands;

use Carbon\CarbonImmutable;

final readonly class SuspendMember
{
    public function __construct(
        public string $memberId,
        public CarbonImmutable $suspendedAt,
        public string $reason,
    ) {}
}
