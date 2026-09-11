<?php

namespace App\Domain\Membership\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

final class MembershipTypeChanged extends ShouldBeStored
{
    public function __construct(
        public readonly string $membershipTypeId,
    ) {}
}
