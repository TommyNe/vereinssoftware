<?php

namespace App\Application\Membership\Commands;

use Carbon\CarbonImmutable;

final readonly class LeaveMember
{
    public function __construct(
        public string $memberId,
        public CarbonImmutable $leftAt,
        public string $reason,
    ) {}
}
