<?php

namespace App\Domain\Membership\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

final class MemberJoinedDepartment extends ShouldBeStored
{
    public function __construct(
        public readonly string $departmentId,
        public readonly string $joinedAt,
    ) {}
}
