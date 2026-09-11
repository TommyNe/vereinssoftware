<?php

namespace App\Application\Membership\Commands;

use Carbon\CarbonImmutable;

final readonly class JoinMemberDepartment
{
    public function __construct(
        public string $memberId,
        public string $departmentId,
        public CarbonImmutable $joinedAt,
    ) {}
}
