<?php

namespace App\Application\Membership\Commands;

use Carbon\CarbonImmutable;

final readonly class LeaveMemberDepartment
{
    public function __construct(
        public string $memberId,
        public string $departmentId,
        public CarbonImmutable $leftAt,
    ) {}
}
